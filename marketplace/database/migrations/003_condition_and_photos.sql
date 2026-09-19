-- 003: new vs used clarity, what's included with a used copy, and the three required photos (disc, box outside, box inside).
SET NAMES utf8mb4;

-- NULL = not stated (old house stock); 1 = included; 0 = not included. Must be answered for new used listings.
ALTER TABLE products
  ADD COLUMN IF NOT EXISTS includes_box       TINYINT(1) NULL COMMENT 'original case / box' AFTER item_condition,
  ADD COLUMN IF NOT EXISTS includes_cover_art TINYINT(1) NULL COMMENT 'cover art / sleeve inlay' AFTER includes_box,
  ADD COLUMN IF NOT EXISTS includes_manual    TINYINT(1) NULL COMMENT 'manual / inserts' AFTER includes_cover_art;

ALTER TABLE product_images
  ADD COLUMN IF NOT EXISTS kind ENUM('disc','box_outside','box_inside','extra') NOT NULL DEFAULT 'extra' AFTER path,
  ADD KEY IF NOT EXISTS idx_images_product (product_id, kind);

-- Sell / trade-in requests: photos the customer sent (JSON list of {kind, path} under uploads/).
ALTER TABLE buyback_requests
  ADD COLUMN IF NOT EXISTS photos TEXT NULL COMMENT 'JSON: [{kind, path}] photos sent by the customer';
