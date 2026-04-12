-- PostgreSQL migration: moderator promotion + alumni post verification + visibility scopes

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS is_moderator BOOLEAN NOT NULL DEFAULT FALSE;

ALTER TABLE posts
    ADD COLUMN IF NOT EXISTS visibility_scope VARCHAR(32) NOT NULL DEFAULT 'all';

ALTER TABLE posts
    ADD COLUMN IF NOT EXISTS moderation_status VARCHAR(32) NOT NULL DEFAULT 'approved';

ALTER TABLE posts
    ADD COLUMN IF NOT EXISTS reviewed_by_user_id BIGINT NULL;

ALTER TABLE posts
    ADD COLUMN IF NOT EXISTS reviewed_at TIMESTAMP NULL;

ALTER TABLE posts
    ADD COLUMN IF NOT EXISTS review_note TEXT NULL;

ALTER TABLE posts
    ADD COLUMN IF NOT EXISTS revision_no INTEGER NOT NULL DEFAULT 1;

ALTER TABLE posts
    ADD COLUMN IF NOT EXISTS pending_revision_no INTEGER NULL;

ALTER TABLE posts
    ADD COLUMN IF NOT EXISTS pending_edit_status VARCHAR(32) NOT NULL DEFAULT 'none';

ALTER TABLE posts
    ADD COLUMN IF NOT EXISTS pending_edit_content_file_path TEXT NULL;

ALTER TABLE posts
    ADD COLUMN IF NOT EXISTS pending_edit_submitted_at TIMESTAMP NULL;

ALTER TABLE posts
    ADD COLUMN IF NOT EXISTS previous_content_file_path TEXT NULL;

ALTER TABLE posts
    ADD COLUMN IF NOT EXISTS previous_revision_no INTEGER NULL;

UPDATE posts
SET moderation_status = 'approved'
WHERE moderation_status IS NULL OR moderation_status = '';

UPDATE posts
SET pending_edit_status = 'none'
WHERE pending_edit_status IS NULL OR pending_edit_status = '';

UPDATE posts
SET revision_no = 1
WHERE revision_no IS NULL OR revision_no < 1;

CREATE INDEX IF NOT EXISTS idx_users_is_moderator ON users(is_moderator);
CREATE INDEX IF NOT EXISTS idx_posts_visibility_scope ON posts(visibility_scope);
CREATE INDEX IF NOT EXISTS idx_posts_moderation_status ON posts(moderation_status);
CREATE INDEX IF NOT EXISTS idx_posts_pending_edit_status ON posts(pending_edit_status);
