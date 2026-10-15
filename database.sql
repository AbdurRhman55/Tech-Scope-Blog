-- =====================================================================
--  Complete Professional Blog Website — Database Schema
--  MySQL 8.x / MariaDB 10.4+  |  Import via phpMyAdmin or:
--    mysql -u root -p < database.sql
-- =====================================================================

CREATE DATABASE IF NOT EXISTS pro_blog
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE pro_blog;

-- ---------------------------------------------------------------------
-- Users (Admin / Editor / Author)
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100)  NOT NULL,
  slug          VARCHAR(120)  NOT NULL,
  email         VARCHAR(190)  NOT NULL UNIQUE,
  password      VARCHAR(255)  NOT NULL,              -- password_hash()
  role          ENUM('admin','editor','author') NOT NULL DEFAULT 'author',
  bio           TEXT          NULL,
  avatar        VARCHAR(255)  NULL,
  website       VARCHAR(255)  NULL,
  twitter       VARCHAR(120)  NULL,
  linkedin      VARCHAR(120)  NULL,
  github        VARCHAR(120)  NULL,
  status        TINYINT(1)    NOT NULL DEFAULT 1,     -- 1 active, 0 disabled
  last_login    DATETIME      NULL,
  created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_slug (slug),
  KEY idx_users_role (role)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Categories (hierarchical — parent_id allows sub-categories later)
-- ---------------------------------------------------------------------
CREATE TABLE categories (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(80)   NOT NULL,
  slug          VARCHAR(100)  NOT NULL UNIQUE,
  description   VARCHAR(255)  NULL,
  parent_id     INT UNSIGNED  NULL,
  meta_title    VARCHAR(190)  NULL,
  meta_desc     VARCHAR(300)  NULL,
  sort_order    INT           NOT NULL DEFAULT 0,
  created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_cat_parent (parent_id),
  CONSTRAINT fk_cat_parent FOREIGN KEY (parent_id)
    REFERENCES categories (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tags
-- ---------------------------------------------------------------------
CREATE TABLE tags (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(60)   NOT NULL,
  slug          VARCHAR(80)   NOT NULL UNIQUE,
  created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Posts
-- ---------------------------------------------------------------------
CREATE TABLE posts (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title          VARCHAR(200)  NOT NULL,
  slug           VARCHAR(230)  NOT NULL UNIQUE,
  excerpt        VARCHAR(500)  NULL,
  content        LONGTEXT      NOT NULL,
  cover_image    VARCHAR(255)  NULL,
  cover_alt      VARCHAR(200)  NULL,                  -- descriptive image alt text
  author_id      INT UNSIGNED  NOT NULL,
  category_id    INT UNSIGNED  NULL,
  status         ENUM('draft','published','scheduled') NOT NULL DEFAULT 'draft',
  is_featured    TINYINT(1)    NOT NULL DEFAULT 0,
  allow_comments TINYINT(1)    NOT NULL DEFAULT 1,
  views          INT UNSIGNED  NOT NULL DEFAULT 0,
  reading_time   TINYINT UNSIGNED NOT NULL DEFAULT 3,
  meta_title     VARCHAR(190)  NULL,
  meta_desc      VARCHAR(300)  NULL,
  published_at   DATETIME      NULL,
  created_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                    ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_posts_status_date (status, published_at),
  KEY idx_posts_category (category_id),
  KEY idx_posts_author (author_id),
  FULLTEXT KEY ft_posts_search (title, excerpt, content),
  CONSTRAINT fk_posts_author   FOREIGN KEY (author_id)
    REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_posts_category FOREIGN KEY (category_id)
    REFERENCES categories (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Post <-> Tag  (many-to-many)
-- ---------------------------------------------------------------------
CREATE TABLE post_tags (
  post_id   INT UNSIGNED NOT NULL,
  tag_id    INT UNSIGNED NOT NULL,
  PRIMARY KEY (post_id, tag_id),
  KEY idx_pt_tag (tag_id),
  CONSTRAINT fk_pt_post FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE,
  CONSTRAINT fk_pt_tag  FOREIGN KEY (tag_id)  REFERENCES tags (id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Editorial briefs (1:1 with posts) — audience, primary question,
-- search intent, outline, references, review workflow
-- ---------------------------------------------------------------------
CREATE TABLE editorial_briefs (
  post_id              INT UNSIGNED NOT NULL  COMMENT '1:1 with posts.id',
  target_audience      VARCHAR(300) NULL      COMMENT 'Who the article is written for',
  primary_question     VARCHAR(300) NULL      COMMENT 'The main question the article must answer',
  search_intent        VARCHAR(30)  NULL      COMMENT 'informational|tutorial|troubleshooting|comparison|evaluation|reference|career',
  supporting_questions TEXT         NULL      COMMENT 'Follow-up questions the article should cover (one per line)',
  outline              TEXT         NULL      COMMENT 'Proposed H2/H3 outline (one heading per line)',
  references_text      TEXT         NULL      COMMENT 'Sources: one per line, "Label - https://..." or bare URL',
  related_slugs        VARCHAR(500) NULL      COMMENT 'Comma-separated slugs of published posts (validated at save)',
  review_status        VARCHAR(20)  NOT NULL DEFAULT 'none'
                       COMMENT 'none|brief_ready|draft_ready|in_review|approved|needs_revision',
  review_notes         TEXT         NULL      COMMENT 'Editor notes for revision cycles',
  updated_by           INT UNSIGNED NULL      COMMENT 'users.id of last editor',
  updated_at           DATETIME     NULL,
  PRIMARY KEY (post_id),
  CONSTRAINT fk_brief_post FOREIGN KEY (post_id)
    REFERENCES posts (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Comments (threaded, moderated)
-- ---------------------------------------------------------------------
CREATE TABLE comments (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_id     INT UNSIGNED NOT NULL,
  parent_id   INT UNSIGNED NULL,
  name        VARCHAR(80)  NOT NULL,
  email       VARCHAR(190) NOT NULL,
  website     VARCHAR(255) NULL,
  content     TEXT         NOT NULL,
  ip_address  VARBINARY(16) NULL,
  status      ENUM('pending','approved','spam','trash') NOT NULL DEFAULT 'pending',
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_comments_post_status (post_id, status),
  KEY idx_comments_status (status),
  CONSTRAINT fk_comments_post FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE,
  CONSTRAINT fk_comments_parent FOREIGN KEY (parent_id) REFERENCES comments (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Media Library
-- ---------------------------------------------------------------------
CREATE TABLE media (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  filename     VARCHAR(255) NOT NULL,
  path         VARCHAR(255) NOT NULL,
  mime_type    VARCHAR(100) NOT NULL,
  size_bytes   INT UNSIGNED NOT NULL DEFAULT 0,
  alt_text     VARCHAR(200) NULL,
  uploaded_by  INT UNSIGNED NULL,
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_media_user (uploaded_by),
  CONSTRAINT fk_media_user FOREIGN KEY (uploaded_by)
    REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Contact Messages
-- ---------------------------------------------------------------------
CREATE TABLE messages (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  email       VARCHAR(190) NOT NULL,
  subject     VARCHAR(200) NOT NULL,
  body        TEXT         NOT NULL,
  is_read     TINYINT(1)   NOT NULL DEFAULT 0,
  ip_address  VARBINARY(16) NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_messages_read (is_read)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Settings (key/value — SEO, branding, socials, ads)
-- ---------------------------------------------------------------------
CREATE TABLE settings (
  setting_key   VARCHAR(80)  NOT NULL PRIMARY KEY,
  setting_value TEXT         NULL,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                               ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================================
--  DEFAULT DATA
-- =====================================================================

-- Admin user  →  email: admin@example.com  |  password: Admin@123
-- CHANGE THIS PASSWORD AFTER FIRST LOGIN.
INSERT INTO users (name, slug, email, password, role, bio) VALUES
('Site Administrator', 'site-admin', 'admin@example.com',
 '$2y$10$.nArmSnjzaXBafmuwZn9pu4j0NH/SLW02QFymE43EpM2VeRMQV2ma', 'admin',
 'Default administrator account.');

-- Categories
INSERT INTO categories (name, slug, description, sort_order) VALUES
('Artificial Intelligence', 'artificial-intelligence', 'AI, machine learning, LLMs and the future of intelligent systems.', 1),
('Technology',             'technology',             'Gadgets, software, internet and the tech industry.', 2),
('Data Science',           'data-science',           'Analytics, visualization and data-driven decision making.', 3),
('Programming',            'programming',            'Languages, frameworks, best practices and clean code.', 4),
('Research',               'research',               'Papers, studies, methods and academic discovery.', 5),
('Student & Career',       'student-career',         'Guidance, skills, exams and career planning.', 6),
('Productivity',           'productivity',           'Habits, tools and systems to do more with less.', 7),
('Freelancing',            'freelancing',            'Digital skills, clients, pricing and remote work.', 8);

-- Settings defaults
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name',        'TechScope'),
('site_tagline',     'Insights on AI, Technology & Digital Skills'),
('site_url',         'http://localhost:8000'),
('site_logo',        ''),
('site_favicon',     ''),
('site_description', 'A professional technology publication covering Artificial Intelligence, Data Science, Programming, Research and Career Guidance.'),
('primary_color',    '#2563EB'),
('accent_color',     '#0EA5E9'),
('contact_email',    'contact@example.com'),
('contact_phone',    ''),
('contact_address',  ''),
('facebook_url',     ''),
('twitter_url',      ''),
('linkedin_url',     ''),
('instagram_url',    ''),
('github_url',        ''),
('youtube_url',      ''),
('ads_header',       ''),
('ads_inpost',       ''),
('ads_footer',       ''),
('ga_measurement_id',''),
('meta_keywords',    'AI, technology, data science, programming, research, careers'),
('allow_comments',   '1'),
('comments_moderation', '1'),
('posts_per_page',   '9'),
('footer_text',      '© ' );
