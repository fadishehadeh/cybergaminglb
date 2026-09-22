-- 005: hardware products, category kinds, SEO/AI settings and a small articles (guides) section.
SET NAMES utf8mb4;

-- Category kind decides which listing rules apply: game = disc/box photos and included items; hardware = specs, warranty, unit photos; digital = gift cards.
ALTER TABLE categories
  ADD COLUMN IF NOT EXISTS kind ENUM('game','hardware','digital') NOT NULL DEFAULT 'hardware' AFTER slug,
  ADD COLUMN IF NOT EXISTS member_listing TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = members and stores may list items in this category';
UPDATE categories SET kind = 'game', member_listing = 1 WHERE slug = 'games';
UPDATE categories SET kind = 'digital' WHERE slug = 'gift-cards';

ALTER TABLE products
  ADD COLUMN IF NOT EXISTS brand           VARCHAR(60)  NULL AFTER title,
  ADD COLUMN IF NOT EXISTS model           VARCHAR(120) NULL AFTER brand,
  ADD COLUMN IF NOT EXISTS specs           TEXT NULL COMMENT 'one "Label: value" per line, shown as a spec table',
  ADD COLUMN IF NOT EXISTS included_items  TEXT NULL COMMENT 'what is in the box, one item per line',
  ADD COLUMN IF NOT EXISTS warranty_months TINYINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS serial_number   VARCHAR(80) NULL COMMENT 'ADMIN ONLY: never shown publicly';

-- Hardware photo kinds: the unit from the front, the back with its ports, and everything that comes in the box (+ optional powered-on shot).
ALTER TABLE product_images
  MODIFY kind ENUM('disc','box_outside','box_inside','extra','unit_front','unit_back','box_accessories','powered_on') NOT NULL DEFAULT 'extra';

CREATE TABLE IF NOT EXISTS articles (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug             VARCHAR(160) NOT NULL UNIQUE,
  title            VARCHAR(200) NOT NULL,
  excerpt          VARCHAR(400) NULL,
  body             MEDIUMTEXT NOT NULL,
  meta_title       VARCHAR(160) NULL,
  meta_description VARCHAR(320) NULL,
  tag              VARCHAR(40) NULL,
  author           VARCHAR(80) NOT NULL DEFAULT 'CyberGaming team',
  is_published     TINYINT(1) NOT NULL DEFAULT 0,
  published_at     DATETIME NULL,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_articles_pub (is_published, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO settings (`key`, `value`) VALUES
  ('seo_ai_crawlers_allowed', '1'),   -- 1 = robots.txt welcomes AI search crawlers (GPTBot, ClaudeBot, PerplexityBot, Google-Extended...)
  ('seo_google_verification', ''),
  ('seo_bing_verification', ''),
  ('seo_default_og_image', ''),
  ('biz_address', ''),
  ('biz_hours', 'Mo-Sa 10:00-20:00'),
  ('biz_price_range', '$$'),
  ('biz_facebook_url', ''),
  ('biz_tiktok_url', ''),
  ('biz_youtube_url', '');
