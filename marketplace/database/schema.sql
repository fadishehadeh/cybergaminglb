-- CyberGaming marketplace schema. Safe to run on an empty database.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(120) NOT NULL,
  email         VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('admin','seller') NOT NULL,
  status        ENUM('active','disabled') NOT NULL DEFAULT 'active',
  last_login_at DATETIME NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sellers are PRIVATE: buyers only ever see the public `code` (or nothing). Never expose name/phone/area on the storefront.
CREATE TABLE IF NOT EXISTS sellers (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id        INT UNSIGNED NULL,
  code           VARCHAR(12) NOT NULL UNIQUE,
  name           VARCHAR(150) NOT NULL,
  phone          VARCHAR(40) NULL,
  area           VARCHAR(120) NULL,
  commission_pct DECIMAL(5,2) NULL COMMENT 'NULL = use the global default from settings',
  payout_method  VARCHAR(120) NULL,
  notes          TEXT NULL,
  status         ENUM('pending','active','suspended') NOT NULL DEFAULT 'active',
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sellers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS platforms (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug       VARCHAR(40) NOT NULL UNIQUE,
  name       VARCHAR(60) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active  TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug            VARCHAR(40) NOT NULL UNIQUE,
  name            VARCHAR(60) NOT NULL,
  sort_order      INT NOT NULL DEFAULT 0,
  is_active       TINYINT(1) NOT NULL DEFAULT 1,
  seo_title       VARCHAR(160) NULL,
  seo_description VARCHAR(320) NULL,
  intro_text      TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  seller_id      INT UNSIGNED NULL COMMENT 'NULL = house inventory (owner-owned, no commission)',
  category_id    INT UNSIGNED NOT NULL,
  platform_id    INT UNSIGNED NULL,
  slug           VARCHAR(190) NOT NULL UNIQUE,
  title          VARCHAR(190) NOT NULL,
  description    TEXT NULL,
  item_condition ENUM('New','Like New','Good','Fair') NOT NULL DEFAULT 'Good',
  edition        VARCHAR(30) NOT NULL DEFAULT 'Standard',
  is_steelbook   TINYINT(1) NOT NULL DEFAULT 0,
  year           SMALLINT NULL,
  genres         VARCHAR(255) NULL COMMENT 'comma separated',
  seller_price   DECIMAL(8,2) NOT NULL COMMENT 'what the seller receives',
  commission_pct DECIMAL(5,2) NOT NULL DEFAULT 0,
  price          DECIMAL(8,2) NOT NULL COMMENT 'what the buyer pays (seller_price + commission)',
  stock          INT NOT NULL DEFAULT 1,
  image          VARCHAR(255) NULL COMMENT 'path relative to public/uploads',
  status         ENUM('pending','active','sold','hidden') NOT NULL DEFAULT 'pending',
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_products_listing (status, category_id, platform_id),
  KEY idx_products_seller (seller_id),
  CONSTRAINT fk_products_seller   FOREIGN KEY (seller_id)   REFERENCES sellers(id) ON DELETE SET NULL,
  CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id),
  CONSTRAINT fk_products_platform FOREIGN KEY (platform_id) REFERENCES platforms(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_images (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  path       VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_images_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Buyers check out as guests. Their details are visible to the admin only, never to sellers.
CREATE TABLE IF NOT EXISTS orders (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code          VARCHAR(20) NOT NULL UNIQUE,
  buyer_name    VARCHAR(120) NOT NULL,
  buyer_phone   VARCHAR(40) NOT NULL,
  buyer_area    VARCHAR(120) NOT NULL,
  buyer_address VARCHAR(255) NULL,
  buyer_note    TEXT NULL,
  status        ENUM('new','confirmed','picked_up','delivered','cancelled') NOT NULL DEFAULT 'new',
  total         DECIMAL(9,2) NOT NULL,
  admin_note    TEXT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_orders_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id     INT UNSIGNED NOT NULL,
  product_id   INT UNSIGNED NULL,
  seller_id    INT UNSIGNED NULL,
  title        VARCHAR(190) NOT NULL,
  qty          INT NOT NULL DEFAULT 1,
  unit_price   DECIMAL(8,2) NOT NULL COMMENT 'buyer price at time of order',
  seller_price DECIMAL(8,2) NOT NULL COMMENT 'seller payout per unit at time of order',
  CONSTRAINT fk_items_order   FOREIGN KEY (order_id)   REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
  CONSTRAINT fk_items_seller  FOREIGN KEY (seller_id)  REFERENCES sellers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One payout row is created per seller order line when the order is marked delivered.
CREATE TABLE IF NOT EXISTS payouts (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  seller_id     INT UNSIGNED NOT NULL,
  order_item_id INT UNSIGNED NULL,
  amount        DECIMAL(9,2) NOT NULL,
  status        ENUM('pending','paid') NOT NULL DEFAULT 'pending',
  paid_at       DATETIME NULL,
  note          VARCHAR(255) NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_payouts_seller FOREIGN KEY (seller_id) REFERENCES sellers(id),
  CONSTRAINT fk_payouts_item   FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS buyback_requests (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code          VARCHAR(20) NOT NULL UNIQUE,
  name          VARCHAR(120) NOT NULL,
  phone         VARCHAR(40) NOT NULL,
  area          VARCHAR(120) NULL,
  kind          ENUM('sell','trade_in') NOT NULL DEFAULT 'sell',
  items         TEXT NOT NULL COMMENT 'JSON: [{title, platform, condition, offer}]',
  offered_total DECIMAL(9,2) NOT NULL DEFAULT 0,
  wanted_items  TEXT NULL COMMENT 'trade-in: the products the customer wants',
  status        ENUM('new','contacted','accepted','completed','declined') NOT NULL DEFAULT 'new',
  admin_note    TEXT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customer-to-customer swaps run through the store as a hub. Public listings show first name + area only.
CREATE TABLE IF NOT EXISTS swap_requests (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code        VARCHAR(20) NOT NULL UNIQUE,
  name        VARCHAR(120) NOT NULL,
  phone       VARCHAR(40) NOT NULL,
  area        VARCHAR(120) NULL,
  platform_id INT UNSIGNED NULL,
  offering    VARCHAR(255) NOT NULL,
  wanting     VARCHAR(255) NOT NULL,
  notes       TEXT NULL,
  is_public   TINYINT(1) NOT NULL DEFAULT 0,
  status      ENUM('new','listed','matched','completed','cancelled') NOT NULL DEFAULT 'new',
  fee         DECIMAL(6,2) NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_swap_platform FOREIGN KEY (platform_id) REFERENCES platforms(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  `key`   VARCHAR(60) NOT NULL PRIMARY KEY,
  `value` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip         VARCHAR(45) NOT NULL,
  email      VARCHAR(190) NOT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_attempts (ip, email, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed data ---------------------------------------------------------------
INSERT IGNORE INTO platforms (slug, name, sort_order) VALUES
  ('ps4', 'PlayStation 4', 1), ('ps5', 'PlayStation 5', 2), ('switch', 'Nintendo Switch', 3),
  ('xbox-one', 'Xbox One', 4), ('xbox-series', 'Xbox Series X|S', 5), ('pc', 'PC', 6);

INSERT IGNORE INTO categories (slug, name, sort_order, seo_title, seo_description, intro_text) VALUES
  ('games', 'Games', 1, 'Used & New Video Games in Lebanon', 'Buy used and new PS4, PS5, Switch and Xbox games in Lebanon. Inspected copies, fair USD prices, delivery across Lebanon.', 'Inspected used and new games for every console, delivered across Lebanon.'),
  ('consoles', 'Consoles', 2, 'Consoles for Sale in Lebanon', 'PlayStation, Xbox and Nintendo consoles for sale in Lebanon. Tested, fair prices, delivery across Lebanon.', 'Tested consoles ready to play.'),
  ('controllers', 'Controllers', 3, 'Game Controllers in Lebanon', 'DualShock, DualSense, Xbox and Switch controllers in Lebanon. Original and tested.', 'Original controllers for PlayStation, Xbox and Switch.'),
  ('accessories', 'Accessories', 4, 'Gaming Accessories in Lebanon', 'Headsets, charging docks, cables, cases and other gaming accessories in Lebanon.', 'Everything to go with your console.'),
  ('gift-cards', 'Gift Cards', 5, 'PlayStation & Xbox Gift Cards in Lebanon', 'PlayStation Store, Xbox and Nintendo eShop cards and subscriptions in Lebanon.', 'Digital cards and subscriptions.');

INSERT IGNORE INTO settings (`key`, `value`) VALUES
  ('commission_pct', '15'),
  ('buyback_pct',    '45'),
  ('tradein_pct',    '50'),
  ('swap_fee',       '3'),
  ('buyback_factor_new',      '100'),
  ('buyback_factor_like_new', '100'),
  ('buyback_factor_good',     '90'),
  ('buyback_factor_fair',     '70'),
  ('whatsapp_number','961'),
  ('site_name',      'CyberGaming Lebanon'),
  ('tagline',        'Buy, sell & trade games and gaming gear in Lebanon'),
  ('contact_email',  ''),
  ('instagram_url',  'https://www.instagram.com/cybergaminglb/'),
  ('hub_address',    'Lebanon');
