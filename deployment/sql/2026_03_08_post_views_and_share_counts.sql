-- Enable dynamic share/view counters for feed posts.

ALTER TABLE posts ADD COLUMN IF NOT EXISTS view_count INTEGER NOT NULL DEFAULT 0;
ALTER TABLE posts ADD COLUMN IF NOT EXISTS share_count INTEGER NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS post_views (
    view_id BIGSERIAL PRIMARY KEY,
    post_id BIGINT NOT NULL REFERENCES posts(post_id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    viewed_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE (post_id, user_id)
);

CREATE INDEX IF NOT EXISTS idx_post_views_post ON post_views(post_id);
CREATE INDEX IF NOT EXISTS idx_post_views_user ON post_views(user_id);

-- Optional backfill if post_views already had data.
UPDATE posts p
SET view_count = v.cnt
FROM (
    SELECT post_id, COUNT(*)::int AS cnt
    FROM post_views
    GROUP BY post_id
) v
WHERE p.post_id = v.post_id;

