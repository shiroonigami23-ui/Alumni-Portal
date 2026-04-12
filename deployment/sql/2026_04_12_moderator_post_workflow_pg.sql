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

UPDATE posts
SET moderation_status = 'approved'
WHERE moderation_status IS NULL OR moderation_status = '';

CREATE INDEX IF NOT EXISTS idx_users_is_moderator ON users(is_moderator);
CREATE INDEX IF NOT EXISTS idx_posts_visibility_scope ON posts(visibility_scope);
CREATE INDEX IF NOT EXISTS idx_posts_moderation_status ON posts(moderation_status);
