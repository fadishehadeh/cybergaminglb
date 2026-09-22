-- 006: PC gaming peripheral categories (hardware kind). Empty categories stay out of navigation and the sitemap until they have products.
SET NAMES utf8mb4;

INSERT IGNORE INTO categories (slug, kind, name, sort_order, is_active, member_listing, seo_title, seo_description, intro_text) VALUES
  ('keyboards', 'hardware', 'Keyboards', 6, 1, 0,
   'Gaming Keyboards in Lebanon: Mechanical and Wireless',
   'Buy gaming and mechanical keyboards in Lebanon. Tested new and used keyboards from top brands, fair USD prices and delivery across Lebanon.',
   'Mechanical, membrane and wireless keyboards, each tested before it ships.'),
  ('mice', 'hardware', 'Mice', 7, 1, 0,
   'Gaming Mice in Lebanon: Wired and Wireless',
   'Gaming mice for sale in Lebanon: wired and wireless, high-DPI sensors, new and used. Every mouse is tested and delivered across Lebanon.',
   'Gaming mice from popular brands, tested and ready to use.'),
  ('mousepads', 'hardware', 'Mousepads', 8, 1, 0,
   'Gaming Mousepads in Lebanon: Desk Mats and Speed Pads',
   'Gaming mousepads and desk mats in Lebanon, from compact speed pads to extra-large desk mats. Delivery across Lebanon.',
   'Speed pads, control pads and full desk mats.'),
  ('headsets', 'hardware', 'Headsets', 9, 1, 0,
   'Gaming Headsets in Lebanon: Wired and Wireless',
   'Gaming headsets for PC, PlayStation, Xbox and Switch in Lebanon. Tested new and used headsets, delivered across Lebanon.',
   'Gaming headsets with microphones for every platform.');

-- Give PC its own platform filter when it does not exist yet (already seeded in schema.sql; kept for safety).
INSERT IGNORE INTO platforms (slug, name, sort_order) VALUES ('pc', 'PC', 6);
