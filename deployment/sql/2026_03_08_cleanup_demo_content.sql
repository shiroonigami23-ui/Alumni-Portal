-- Cleanup demo/test content for a clean faculty-facing environment.
-- Safe on partially provisioned DBs (checks table existence).

DO $$
BEGIN
    IF to_regclass('public.comment_reactions') IS NOT NULL THEN
        TRUNCATE TABLE comment_reactions RESTART IDENTITY CASCADE;
    END IF;
    IF to_regclass('public.post_reactions') IS NOT NULL THEN
        TRUNCATE TABLE post_reactions RESTART IDENTITY CASCADE;
    END IF;
    IF to_regclass('public.pinned_posts') IS NOT NULL THEN
        TRUNCATE TABLE pinned_posts RESTART IDENTITY CASCADE;
    END IF;
    IF to_regclass('public.comments') IS NOT NULL THEN
        TRUNCATE TABLE comments RESTART IDENTITY CASCADE;
    END IF;
    IF to_regclass('public.posts') IS NOT NULL THEN
        TRUNCATE TABLE posts RESTART IDENTITY CASCADE;
    END IF;
    IF to_regclass('public.scheduled_posts') IS NOT NULL THEN
        TRUNCATE TABLE scheduled_posts RESTART IDENTITY CASCADE;
    END IF;
    IF to_regclass('public.archived_posts') IS NOT NULL THEN
        TRUNCATE TABLE archived_posts RESTART IDENTITY CASCADE;
    END IF;

    IF to_regclass('public.messages') IS NOT NULL THEN
        TRUNCATE TABLE messages RESTART IDENTITY CASCADE;
    END IF;
    IF to_regclass('public.calls') IS NOT NULL THEN
        TRUNCATE TABLE calls RESTART IDENTITY CASCADE;
    END IF;

    IF to_regclass('public.job_applications') IS NOT NULL THEN
        TRUNCATE TABLE job_applications RESTART IDENTITY CASCADE;
    END IF;
    IF to_regclass('public.jobs') IS NOT NULL THEN
        TRUNCATE TABLE jobs RESTART IDENTITY CASCADE;
    END IF;

    IF to_regclass('public.mentorship_requests') IS NOT NULL THEN
        TRUNCATE TABLE mentorship_requests RESTART IDENTITY CASCADE;
    END IF;

    IF to_regclass('public.notifications') IS NOT NULL THEN
        TRUNCATE TABLE notifications RESTART IDENTITY CASCADE;
    END IF;
END $$;

