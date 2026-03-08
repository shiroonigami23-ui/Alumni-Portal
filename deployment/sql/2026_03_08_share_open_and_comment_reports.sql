-- Share count should increment only on unique shared-link open by another user.
-- Adds share-link/open tracking and comment report support.

ALTER TABLE posts ADD COLUMN IF NOT EXISTS share_count INTEGER NOT NULL DEFAULT 0;
ALTER TABLE comments ADD COLUMN IF NOT EXISTS report_count INTEGER NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS post_share_links (
    share_link_id BIGSERIAL PRIMARY KEY,
    post_id BIGINT NOT NULL REFERENCES posts(post_id) ON DELETE CASCADE,
    sharer_user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    token VARCHAR(128) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE (post_id, sharer_user_id)
);

CREATE TABLE IF NOT EXISTS post_share_opens (
    share_open_id BIGSERIAL PRIMARY KEY,
    post_id BIGINT NOT NULL REFERENCES posts(post_id) ON DELETE CASCADE,
    viewer_user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    share_link_id BIGINT NULL REFERENCES post_share_links(share_link_id) ON DELETE SET NULL,
    opened_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE (post_id, viewer_user_id)
);

CREATE INDEX IF NOT EXISTS idx_post_share_links_post ON post_share_links(post_id);
CREATE INDEX IF NOT EXISTS idx_post_share_links_sharer ON post_share_links(sharer_user_id);
CREATE INDEX IF NOT EXISTS idx_post_share_opens_post ON post_share_opens(post_id);
CREATE INDEX IF NOT EXISTS idx_post_share_opens_viewer ON post_share_opens(viewer_user_id);

CREATE TABLE IF NOT EXISTS comment_reports (
    comment_report_id BIGSERIAL PRIMARY KEY,
    comment_id BIGINT NOT NULL REFERENCES comments(comment_id) ON DELETE CASCADE,
    reporter_user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    reason VARCHAR(50) NOT NULL DEFAULT 'spam',
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE (comment_id, reporter_user_id)
);

