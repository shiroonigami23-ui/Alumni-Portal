-- Performance and integrity hardening for feed interactions.
-- Safe to run multiple times.

BEGIN;

-- Remove duplicate likes before enforcing uniqueness.
WITH dups AS (
    SELECT ctid
    FROM (
        SELECT
            ctid,
            ROW_NUMBER() OVER (PARTITION BY user_id, post_id ORDER BY ctid) AS rn
        FROM likes
    ) t
    WHERE t.rn > 1
)
DELETE FROM likes l
USING dups d
WHERE l.ctid = d.ctid;

-- Keep post counters consistent.
UPDATE posts p
SET reaction_count = COALESCE(x.cnt, 0)
FROM (
    SELECT post_id, COUNT(*)::int AS cnt
    FROM likes
    GROUP BY post_id
) x
WHERE p.post_id = x.post_id;

UPDATE posts
SET reaction_count = 0
WHERE post_id NOT IN (SELECT DISTINCT post_id FROM likes);

UPDATE posts p
SET comment_count = COALESCE(x.cnt, 0)
FROM (
    SELECT post_id, COUNT(*)::int AS cnt
    FROM comments
    GROUP BY post_id
) x
WHERE p.post_id = x.post_id;

UPDATE posts
SET comment_count = 0
WHERE post_id NOT IN (SELECT DISTINCT post_id FROM comments);

-- Core indexes for feed read path.
CREATE INDEX IF NOT EXISTS idx_posts_status_pinned_created
    ON posts (status, is_pinned, created_at DESC);

CREATE INDEX IF NOT EXISTS idx_posts_user_created
    ON posts (user_id, created_at DESC);

CREATE INDEX IF NOT EXISTS idx_comments_post_created
    ON comments (post_id, created_at ASC);

CREATE INDEX IF NOT EXISTS idx_likes_post
    ON likes (post_id);

CREATE UNIQUE INDEX IF NOT EXISTS uq_likes_user_post
    ON likes (user_id, post_id);

CREATE INDEX IF NOT EXISTS idx_profiles_user
    ON profiles (user_id);

CREATE UNIQUE INDEX IF NOT EXISTS uq_users_email_lower
    ON users (lower(email));

COMMIT;

ANALYZE users;
ANALYZE profiles;
ANALYZE posts;
ANALYZE comments;
ANALYZE likes;

