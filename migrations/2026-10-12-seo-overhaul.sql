-- =====================================================================
--  Migration: SEO & content-quality overhaul (2026-10-12)
--  Safe, backward-compatible — adds one nullable column only.
--  No existing rows are modified or deleted.
--
--  Apply:  mysql -u root -p pro_blog < migrations/2026-10-12-seo-overhaul.sql
-- =====================================================================

USE pro_blog;

-- Cover-image alt text so editors can provide accurate, descriptive
-- alt attributes instead of reusing the article title everywhere.
-- NULL = fall back to the automatic category/topic description.
ALTER TABLE posts
  ADD COLUMN cover_alt VARCHAR(200) NULL AFTER cover_image;
