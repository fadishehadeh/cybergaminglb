-- 004: local vs remote delivery.
--   local  = our own courier (Beirut and surroundings): flat $5, courier can inspect on the spot, cash on delivery allowed.
--   remote = third-party courier: cannot inspect, so purchases are prepaid, sellers ship to our hub, rejected items follow a decision flow.
SET NAMES utf8mb4;

ALTER TABLE delivery_zones ADD COLUMN IF NOT EXISTS mode ENUM('local','remote') NOT NULL DEFAULT 'remote' AFTER fee;

UPDATE delivery_zones SET fee = 5, mode = 'local', sort_order = 1 WHERE name = 'Beirut';
INSERT IGNORE INTO delivery_zones (name, fee, mode, sort_order) VALUES
  ('Baabda', 5, 'local', 2),
  ('Dbayeh and Metn coast', 5, 'local', 3),
  ('Jounieh and Keserwan coast', 5, 'local', 4),
  ('Beirut south (Choueifat to Khalde)', 5, 'local', 5);

UPDATE delivery_zones SET name = 'Mount Lebanon (mountain areas)', fee = 6, mode = 'remote', sort_order = 10 WHERE name = 'Mount Lebanon';
UPDATE delivery_zones SET fee = 6, mode = 'remote', sort_order = 11 WHERE name = 'North';
UPDATE delivery_zones SET fee = 6, mode = 'remote', sort_order = 12 WHERE name = 'South';
UPDATE delivery_zones SET fee = 6, mode = 'remote', sort_order = 13 WHERE name = 'Nabatieh';
UPDATE delivery_zones SET fee = 6, mode = 'remote', sort_order = 14 WHERE name = 'Bekaa';
UPDATE delivery_zones SET fee = 6, mode = 'remote', sort_order = 15 WHERE name = 'Baalbek-Hermel';
UPDATE delivery_zones SET fee = 6, mode = 'remote', sort_order = 16 WHERE name = 'Akkar';

-- Orders: which zone/mode they were delivered under, and whether payment must arrive BEFORE dispatch (remote and digital orders).
ALTER TABLE orders
  ADD COLUMN IF NOT EXISTS zone           VARCHAR(80) NULL AFTER buyer_area,
  ADD COLUMN IF NOT EXISTS zone_mode      ENUM('local','remote','digital') NULL AFTER zone,
  ADD COLUMN IF NOT EXISTS payment_status ENUM('not_required','awaiting','received') NOT NULL DEFAULT 'not_required' AFTER credit_used;

-- Sell / trade-in requests: where the seller is, pickup fee deducted from their payout, and the inspection-rejected decision flow.
--   collected -> passed  -> completed (final amount minus pickup fee, as cash or credit)
--   collected -> rejected -> customer chooses: new_offer (accept revised_amount) | return (pays return_fee on delivery) | recycle (we keep it, no payment)
ALTER TABLE buyback_requests
  MODIFY status ENUM('new','contacted','offered','accepted','collected','completed','declined','cancelled','rejected','return_pending','returned','recycled') NOT NULL DEFAULT 'new',
  ADD COLUMN IF NOT EXISTS zone              VARCHAR(80) NULL AFTER area,
  ADD COLUMN IF NOT EXISTS zone_mode         ENUM('local','remote') NULL AFTER zone,
  ADD COLUMN IF NOT EXISTS pickup_fee        DECIMAL(6,2) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS inspection_result ENUM('pending','passed','rejected') NOT NULL DEFAULT 'pending',
  ADD COLUMN IF NOT EXISTS reject_reason     VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS revised_amount    DECIMAL(9,2) NULL,
  ADD COLUMN IF NOT EXISTS reject_choice     ENUM('new_offer','return','recycle') NULL,
  ADD COLUMN IF NOT EXISTS reject_choice_at  DATETIME NULL,
  ADD COLUMN IF NOT EXISTS return_fee        DECIMAL(6,2) NULL,
  ADD COLUMN IF NOT EXISTS hold_until        DATE NULL COMMENT 'undecided rejected items are recycled after this date';

UPDATE settings SET `value` = '6' WHERE `key` = 'delivery_fee';
INSERT IGNORE INTO settings (`key`, `value`) VALUES
  ('remote_prepay_required', '1'),   -- 1 = customers outside the local area must pay before we ship
  ('remote_cod_after_orders', '3'),  -- ...unless they already have this many delivered orders (0 = never allow cash on delivery outside)
  ('remote_min_sell_value', '25'),   -- minimum estimated value of a shipment from outside the local area (0 = no minimum)
  ('pickup_fee', '5'),               -- charged to sellers for a courier pickup, deducted from their payout
  ('return_fee', '10'),              -- pickup + return trip, paid by the seller in cash when a rejected item is sent back
  ('reject_hold_days', '14');        -- undecided rejected items are recycled after this many days
