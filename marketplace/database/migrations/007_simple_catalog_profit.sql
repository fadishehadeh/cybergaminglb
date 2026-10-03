-- 007: cost/profit tracking for the simplified catalogue (customer accounts, store sellers, buy-back/
-- trade-in/swap are retired — house stock only, guest checkout). Nothing from the retired feature is
-- dropped: the tables stay, in case it is ever switched back on.
SET NAMES utf8mb4;

ALTER TABLE products
  ADD COLUMN IF NOT EXISTS cost_price DECIMAL(8,2) NULL COMMENT 'what you paid; admin-only, never shown publicly' AFTER seller_price;

-- Snapshot of the item's cost at the moment it was ordered, so profit on past orders stays correct even
-- if the product's cost_price is edited later. NULL when the cost was not entered at the time.
ALTER TABLE order_items
  ADD COLUMN IF NOT EXISTS cost_price DECIMAL(8,2) NULL AFTER seller_price;
