-- 002: digital goods (gift cards, Steam gifts) behind a master switch, and anonymous public aliases for accounts.
SET NAMES utf8mb4;

-- Public alias: the ONLY identifier other users may ever see (e.g. "SwiftFalcon42"). Assigned automatically, never chosen or changed by the user.
ALTER TABLE users ADD COLUMN IF NOT EXISTS alias VARCHAR(30) NULL AFTER name;
CREATE UNIQUE INDEX IF NOT EXISTS uq_users_alias ON users (alias);

ALTER TABLE products
  ADD COLUMN IF NOT EXISTS is_digital     TINYINT(1) NOT NULL DEFAULT 0 AFTER is_steelbook,
  ADD COLUMN IF NOT EXISTS digital_kind   ENUM('gift_card','steam_gift','game_key') NULL AFTER is_digital,
  ADD COLUMN IF NOT EXISTS digital_region VARCHAR(40) NULL AFTER digital_kind,
  ADD KEY IF NOT EXISTS idx_products_digital (is_digital, status);

ALTER TABLE order_items ADD COLUMN IF NOT EXISTS is_digital TINYINT(1) NOT NULL DEFAULT 0 AFTER seller_price;

-- Master switch. 0 = digital goods are invisible everywhere on the storefront; 1 = they appear.
INSERT IGNORE INTO settings (`key`, `value`) VALUES ('digital_enabled', '0');
