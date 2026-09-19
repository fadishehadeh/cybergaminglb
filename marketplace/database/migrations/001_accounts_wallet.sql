-- 001: customer accounts, credit wallet, offers workflow, delivery fees, member sellers.
SET NAMES utf8mb4;

ALTER TABLE users MODIFY role ENUM('admin','seller','customer') NOT NULL;
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS phone   VARCHAR(40)  NULL AFTER email,
  ADD COLUMN IF NOT EXISTS area    VARCHAR(120) NULL AFTER phone,
  ADD COLUMN IF NOT EXISTS address VARCHAR(255) NULL AFTER area,
  ADD COLUMN IF NOT EXISTS credit_balance DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'cached SUM(credit_ledger.amount); ledger is the source of truth';
CREATE UNIQUE INDEX IF NOT EXISTS uq_users_phone ON users (phone);

-- Append-only wallet ledger. Rows are never updated or deleted; corrections are new rows.
CREATE TABLE IF NOT EXISTS credit_ledger (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED NOT NULL,
  amount        DECIMAL(10,2) NOT NULL COMMENT 'signed: + credit added, - credit spent',
  type          ENUM('offer_credit','payout_credit','order_payment','order_refund','admin_adjust') NOT NULL,
  ref_type      VARCHAR(30) NULL,
  ref_id        INT UNSIGNED NULL,
  note          VARCHAR(255) NULL,
  balance_after DECIMAL(10,2) NOT NULL,
  created_by    INT UNSIGNED NULL COMMENT 'admin user id when done by staff',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_ledger_user (user_id, created_at),
  KEY idx_ledger_ref (type, ref_type, ref_id),
  CONSTRAINT fk_ledger_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders: linked to an account (optional), delivery fee, paying with credit.
--   total       = items subtotal (unchanged meaning)
--   grand_total = total + delivery_fee
--   credit_used = paid from the wallet;  cash due on delivery = grand_total - credit_used
ALTER TABLE orders
  ADD COLUMN IF NOT EXISTS user_id      INT UNSIGNED NULL AFTER code,
  ADD COLUMN IF NOT EXISTS delivery_fee DECIMAL(8,2) NOT NULL DEFAULT 0 AFTER total,
  ADD COLUMN IF NOT EXISTS grand_total  DECIMAL(9,2) NOT NULL DEFAULT 0 AFTER delivery_fee,
  ADD COLUMN IF NOT EXISTS credit_used  DECIMAL(9,2) NOT NULL DEFAULT 0 AFTER grand_total,
  ADD KEY IF NOT EXISTS idx_orders_user (user_id),
  ADD CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

-- Sell / trade-in requests become an offer workflow:
-- new -> offered (we set offer_cash / offer_credit) -> accepted (customer picks a method) -> collected (we have the games)
-- -> completed (inspected; final_amount paid as cash or added to the wallet) | declined | cancelled
ALTER TABLE buyback_requests
  MODIFY status ENUM('new','contacted','offered','accepted','collected','completed','declined','cancelled') NOT NULL DEFAULT 'new',
  ADD COLUMN IF NOT EXISTS user_id          INT UNSIGNED NULL AFTER code,
  ADD COLUMN IF NOT EXISTS estimate_cash    DECIMAL(9,2) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS estimate_credit  DECIMAL(9,2) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS preferred_method ENUM('cash','credit') NOT NULL DEFAULT 'credit',
  ADD COLUMN IF NOT EXISTS collection       ENUM('dropoff','pickup') NOT NULL DEFAULT 'dropoff',
  ADD COLUMN IF NOT EXISTS pickup_note      VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS offer_cash       DECIMAL(9,2) NULL,
  ADD COLUMN IF NOT EXISTS offer_credit     DECIMAL(9,2) NULL,
  ADD COLUMN IF NOT EXISTS offered_at       DATETIME NULL,
  ADD COLUMN IF NOT EXISTS accepted_method  ENUM('cash','credit') NULL,
  ADD COLUMN IF NOT EXISTS accepted_at      DATETIME NULL,
  ADD COLUMN IF NOT EXISTS collected_at     DATETIME NULL,
  ADD COLUMN IF NOT EXISTS final_amount     DECIMAL(9,2) NULL,
  ADD COLUMN IF NOT EXISTS final_method     ENUM('cash','credit') NULL,
  ADD COLUMN IF NOT EXISTS completed_at     DATETIME NULL,
  ADD KEY IF NOT EXISTS idx_buyback_user (user_id),
  ADD CONSTRAINT fk_buyback_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

-- Members (regular customers who list games for other members) are sellers of type 'member'.
ALTER TABLE sellers ADD COLUMN IF NOT EXISTS type ENUM('store','member') NOT NULL DEFAULT 'store' AFTER code;
-- A payout can be settled in cash or added to the seller's wallet as credit.
ALTER TABLE payouts ADD COLUMN IF NOT EXISTS method ENUM('cash','credit') NULL AFTER status;

CREATE TABLE IF NOT EXISTS delivery_zones (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(80) NOT NULL UNIQUE,
  fee        DECIMAL(6,2) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  is_active  TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO delivery_zones (name, fee, sort_order) VALUES
  ('Beirut', 3, 1), ('Mount Lebanon', 4, 2), ('North', 5, 3), ('South', 5, 4),
  ('Nabatieh', 5, 5), ('Bekaa', 6, 6), ('Baalbek-Hermel', 6, 7), ('Akkar', 6, 8);

INSERT IGNORE INTO settings (`key`, `value`) VALUES
  ('member_commission_pct', '10'),
  ('delivery_fee',          '4'),
  ('free_delivery_over',    '0');
