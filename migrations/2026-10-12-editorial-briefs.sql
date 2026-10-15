-- =====================================================================
--  Migration: Editorial brief & review workflow (2026-10-12)
--  Safe & backward-compatible: creates ONE new table, touches no
--  existing rows or columns. Deleting a post cascades to its brief.
--
--  Backup taken before applying: backups/pro_blog-pre-editorial-*.sql
--  Apply:  mysql -u root -p pro_blog < migrations/2026-10-12-editorial-briefs.sql
--  (Skip if your database.sql already contains editorial_briefs —
--   the table is included in the current base schema.)
-- =====================================================================

USE pro_blog;

CREATE TABLE IF NOT EXISTS editorial_briefs (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
