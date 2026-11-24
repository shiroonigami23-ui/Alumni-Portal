-- ============================================================================
-- RJIT Alumni Portal — complete PostgreSQL schema (PostgreSQL 13+)
--
-- Fresh install (recommended — also creates feature tables):
--   php scripts/install_db.php            (uses DB_* env vars)
-- or manually:
--   createdb alumni_portal && psql -d alumni_portal -f deployment/schema_pg.sql
--
-- Idempotent: safe to re-run. Feature tables that the app also creates lazily
-- (mentorship, messages extras, referrals, settings …) are included so a new
-- database is complete from the start.
-- Seed accounts use the password  ChangeMe@123  — change them immediately.
-- ============================================================================

-- ---------------------------------------------------------------- enum types
DO $$ BEGIN
    CREATE TYPE post_status AS ENUM ('published','draft','scheduled','archived','shadow_banned','deleted','hidden','pending');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;
DO $$ BEGIN
    CREATE TYPE event_status AS ENUM ('pending_approval','approved','rejected','cancelled','completed','live');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;
DO $$ BEGIN
    CREATE TYPE event_visibility AS ENUM ('public','alumni_only','students_only','faculty_only','private','all');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;
DO $$ BEGIN
    CREATE TYPE rsvp_status AS ENUM ('attending','going','maybe','interested','not_going','cancelled','waitlist','waitlisted');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;
DO $$ BEGIN
    CREATE TYPE report_status AS ENUM ('pending','reviewed','resolved','rejected','dismissed');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;
DO $$ BEGIN
    CREATE TYPE report_reason AS ENUM ('spam','harassment','hate_speech','violence','inappropriate','misinformation','fake_account','impersonation','scam','nudity','self_harm','copyright','other');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;
DO $$ BEGIN
    CREATE TYPE notification_type AS ENUM (
        'general','system','admin','announcement','new_post','new_comment','comment','reply','mention','like','reaction',
        'repost','share','new_message','message','call_invite','follow','connection','accepted','new_applicant','job',
        'event','event_reminder','registration_pending','approval','mentorship','mentorship_request','referral',
        'report','moderation','warning','poll','faculty','active','badge','follow_request'
    );
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

-- ---------------------------------------------------------------- core
CREATE TABLE IF NOT EXISTS users (
    user_id BIGSERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'student' CHECK (role IN ('student','alumni','faculty','admin')),
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    can_post BOOLEAN NOT NULL DEFAULT TRUE,
    is_moderator BOOLEAN NOT NULL DEFAULT FALSE,
    total_posts INT NOT NULL DEFAULT 0,
    total_likes_received INT NOT NULL DEFAULT 0,
    login_streak INT NOT NULL DEFAULT 0,
    suspension_expires_at TIMESTAMP NULL,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_users_role_status ON users (role, status);
CREATE INDEX IF NOT EXISTS idx_users_is_moderator ON users (is_moderator);

CREATE TABLE IF NOT EXISTS profiles (
    profile_id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL UNIQUE REFERENCES users(user_id) ON DELETE CASCADE,
    full_name VARCHAR(255) NULL,
    bio TEXT NULL,
    skills TEXT NULL,
    tech_stack TEXT NULL,
    profile_picture_url TEXT NULL,
    cover_photo_url TEXT NULL,
    joining_year INT NULL,
    graduation_year INT NULL,
    course VARCHAR(100) NULL,
    branch VARCHAR(150) NULL,
    current_company VARCHAR(255) NULL,
    job_role VARCHAR(255) NULL,
    department VARCHAR(150) NULL,
    designation VARCHAR(150) NULL,
    specialization VARCHAR(255) NULL,
    office_location VARCHAR(255) NULL,
    contact_number VARCHAR(50) NULL,
    personal_website VARCHAR(255) NULL,
    location_city VARCHAR(120) NULL,
    location_country VARCHAR(120) NULL,
    linkedin_url VARCHAR(255) NULL,
    github_url VARCHAR(255) NULL,
    twitter_url VARCHAR(255) NULL,
    help_alumni_mates TEXT NULL,
    is_private BOOLEAN NOT NULL DEFAULT FALSE,
    show_email BOOLEAN NOT NULL DEFAULT TRUE,
    show_contact BOOLEAN NOT NULL DEFAULT FALSE,
    open_to_mentor BOOLEAN NOT NULL DEFAULT FALSE,
    open_to_refer BOOLEAN NOT NULL DEFAULT FALSE,
    open_to_work BOOLEAN NOT NULL DEFAULT FALSE,
    pronouns VARCHAR(40) NULL,
    roll_number VARCHAR(80) NULL,
    year_of_study VARCHAR(80) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_profiles_branch ON profiles (branch);
CREATE INDEX IF NOT EXISTS idx_profiles_company ON profiles (current_company);
CREATE INDEX IF NOT EXISTS idx_profiles_city ON profiles (location_city);
CREATE INDEX IF NOT EXISTS idx_profiles_grad_year ON profiles (graduation_year);

CREATE TABLE IF NOT EXISTS students (
    student_id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NULL UNIQUE REFERENCES users(user_id) ON DELETE CASCADE,
    email VARCHAR(255) NULL,
    full_name VARCHAR(255) NULL,
    roll_number VARCHAR(80) NULL,
    course VARCHAR(100) NULL,
    branch VARCHAR(150) NULL,
    graduation_year INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS sessions (
    session_id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(64) NULL,
    user_agent TEXT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_sessions_user ON sessions (user_id);
CREATE INDEX IF NOT EXISTS idx_sessions_expiry ON sessions (expires_at);

CREATE TABLE IF NOT EXISTS alumni_tokens (
    token_id BIGSERIAL PRIMARY KEY,
    token VARCHAR(120) NOT NULL UNIQUE,
    used BOOLEAN NOT NULL DEFAULT FALSE,
    used_by_email VARCHAR(255) NULL,
    used_by_user_id BIGINT NULL REFERENCES users(user_id) ON DELETE SET NULL,
    generated_by_admin_id BIGINT NULL REFERENCES users(user_id) ON DELETE SET NULL,
    expires_at TIMESTAMP NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS invite_tokens (
    token_id BIGSERIAL PRIMARY KEY,
    token VARCHAR(120) NOT NULL UNIQUE,
    email VARCHAR(255) NULL,
    usage_limit INT NOT NULL DEFAULT 1,
    used_count INT NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_by_user_id BIGINT NULL REFERENCES users(user_id) ON DELETE SET NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);


-- ---------------------------------------------------------------- feed
CREATE TABLE IF NOT EXISTS posts (
    post_id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    title VARCHAR(255) NULL,
    content_file_path TEXT NULL,
    post_type VARCHAR(50) NOT NULL DEFAULT 'text',
    status post_status NOT NULL DEFAULT 'published',
    moderation_status VARCHAR(32) NOT NULL DEFAULT 'approved',
    visibility_scope VARCHAR(32) NOT NULL DEFAULT 'all',
    reviewed_by_user_id BIGINT NULL REFERENCES users(user_id) ON DELETE SET NULL,
    reviewed_at TIMESTAMP NULL,
    review_note TEXT NULL,
    revision_no INTEGER NOT NULL DEFAULT 1,
    pending_revision_no INTEGER NULL,
    pending_edit_status VARCHAR(32) NOT NULL DEFAULT 'none',
    pending_edit_content_file_path TEXT NULL,
    pending_edit_submitted_at TIMESTAMP NULL,
    previous_content_file_path TEXT NULL,
    previous_revision_no INTEGER NULL,
    thumbnail_url TEXT NULL,
    comments_enabled BOOLEAN NOT NULL DEFAULT TRUE,
    is_pinned BOOLEAN NOT NULL DEFAULT FALSE,
    is_edited BOOLEAN NOT NULL DEFAULT FALSE,
    last_edited_at TIMESTAMP NULL,
    poll_id BIGINT NULL,
    reaction_count INT NOT NULL DEFAULT 0,
    comment_count INT NOT NULL DEFAULT 0,
    view_count INT NOT NULL DEFAULT 0,
    share_count INT NOT NULL DEFAULT 0,
    repost_count INT NOT NULL DEFAULT 0,
    report_count INT NOT NULL DEFAULT 0,
    publish_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_posts_status_created ON posts (status, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_posts_user_created ON posts (user_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_posts_moderation_status ON posts (moderation_status);
CREATE INDEX IF NOT EXISTS idx_posts_visibility_scope ON posts (visibility_scope);
CREATE INDEX IF NOT EXISTS idx_posts_pending_edit_status ON posts (pending_edit_status);

CREATE TABLE IF NOT EXISTS comments (
    comment_id BIGSERIAL PRIMARY KEY,
    post_id BIGINT NOT NULL REFERENCES posts(post_id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    parent_comment_id BIGINT NULL REFERENCES comments(comment_id) ON DELETE CASCADE,
    content_file_path TEXT NOT NULL,
    depth_level INT NOT NULL DEFAULT 0,
    reaction_count INT NOT NULL DEFAULT 0,
    report_count INT NOT NULL DEFAULT 0,
    is_edited BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_comments_post_created ON comments (post_id, created_at);
CREATE INDEX IF NOT EXISTS idx_comments_parent ON comments (parent_comment_id);

CREATE TABLE IF NOT EXISTS likes (
    like_id BIGSERIAL PRIMARY KEY,
    post_id BIGINT NOT NULL REFERENCES posts(post_id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    reaction_type VARCHAR(20) NOT NULL DEFAULT 'like',
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE (post_id, user_id)
);

CREATE TABLE IF NOT EXISTS comment_reactions (
    comment_reaction_id BIGSERIAL PRIMARY KEY,
    comment_id BIGINT NOT NULL REFERENCES comments(comment_id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    reaction_type VARCHAR(20) NOT NULL DEFAULT 'like',
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE (comment_id, user_id)
);


CREATE TABLE IF NOT EXISTS pinned_posts (
    pin_id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    post_id BIGINT NOT NULL REFERENCES posts(post_id) ON DELETE CASCADE,
    pin_order INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE (user_id, post_id)
);

CREATE TABLE IF NOT EXISTS polls (
    poll_id BIGSERIAL PRIMARY KEY,
    post_id BIGINT NULL REFERENCES posts(post_id) ON DELETE CASCADE,
    question TEXT NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE TABLE IF NOT EXISTS poll_options (
    option_id BIGSERIAL PRIMARY KEY,
    poll_id BIGINT NOT NULL REFERENCES polls(poll_id) ON DELETE CASCADE,
    option_text VARCHAR(255) NOT NULL,
    option_order INT NOT NULL DEFAULT 0,
    vote_count INT NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS poll_votes (
    vote_id BIGSERIAL PRIMARY KEY,
    poll_id BIGINT NOT NULL REFERENCES polls(poll_id) ON DELETE CASCADE,
    option_id BIGINT NOT NULL REFERENCES poll_options(option_id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE (poll_id, user_id)
);

CREATE TABLE IF NOT EXISTS scheduled_posts (
    scheduled_id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    content_path TEXT NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'text',
    publish_at TIMESTAMP NOT NULL,
    visibility_scope VARCHAR(32) NOT NULL DEFAULT 'all',
    comments_enabled BOOLEAN NOT NULL DEFAULT TRUE,
    poll_json TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    published_post_id BIGINT NULL,
    last_error TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_scheduled_posts_due ON scheduled_posts (status, publish_at);

CREATE TABLE IF NOT EXISTS archived_posts (
    archive_id BIGSERIAL PRIMARY KEY,
    original_post_id BIGINT NOT NULL,
    user_id BIGINT NULL REFERENCES users(user_id) ON DELETE SET NULL,
    content_file_path TEXT NULL,
    post_type VARCHAR(50) NULL,
    final_reaction_count INT NOT NULL DEFAULT 0,
    final_comment_count INT NOT NULL DEFAULT 0,
    final_view_count INT NOT NULL DEFAULT 0,
    original_created_at TIMESTAMP NULL,
    content_deleted BOOLEAN NOT NULL DEFAULT FALSE,
    content_deleted_at TIMESTAMP NULL,
    archived_at TIMESTAMP NOT NULL DEFAULT NOW()
);



-- ---------------------------------------------------------------- people & safety
CREATE TABLE IF NOT EXISTS connections (
    connection_id BIGSERIAL PRIMARY KEY,
    requester_user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    addressee_user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    status VARCHAR(20) NOT NULL DEFAULT 'accepted',
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    accepted_at TIMESTAMP NULL DEFAULT NOW(),
    UNIQUE (requester_user_id, addressee_user_id)
);
CREATE INDEX IF NOT EXISTS idx_connections_addressee ON connections (addressee_user_id, status);
CREATE INDEX IF NOT EXISTS idx_connections_pair ON connections (requester_user_id, addressee_user_id, status);

-- Who viewed your profile (one row per viewer per day)
CREATE TABLE IF NOT EXISTS profile_views (
    viewer_user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    viewed_user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    view_day DATE NOT NULL DEFAULT CURRENT_DATE,
    viewed_at TIMESTAMP NOT NULL DEFAULT NOW(),
    PRIMARY KEY (viewer_user_id, viewed_user_id, view_day)
);
CREATE INDEX IF NOT EXISTS idx_profile_views_viewed ON profile_views (viewed_user_id, viewed_at DESC);

CREATE TABLE IF NOT EXISTS blocks (
    block_id BIGSERIAL PRIMARY KEY,
    blocker_user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    blocked_user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE (blocker_user_id, blocked_user_id)
);

CREATE TABLE IF NOT EXISTS reports (
    report_id BIGSERIAL PRIMARY KEY,
    reporter_user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    reported_user_id BIGINT NULL REFERENCES users(user_id) ON DELETE SET NULL,
    reported_post_id BIGINT NULL REFERENCES posts(post_id) ON DELETE SET NULL,
    reported_comment_id BIGINT NULL REFERENCES comments(comment_id) ON DELETE SET NULL,
    reason report_reason NOT NULL DEFAULT 'other',
    custom_reason TEXT NULL,
    description TEXT NULL,
    status report_status NOT NULL DEFAULT 'pending',
    reviewed_by_admin_id BIGINT NULL REFERENCES users(user_id) ON DELETE SET NULL,
    admin_notes TEXT NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_reports_status ON reports (status, created_at);

CREATE TABLE IF NOT EXISTS comment_reports (
    report_id BIGSERIAL PRIMARY KEY,
    comment_id BIGINT NOT NULL REFERENCES comments(comment_id) ON DELETE CASCADE,
    reporter_user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    reason VARCHAR(50) NOT NULL DEFAULT 'spam',
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE (comment_id, reporter_user_id)
);

CREATE TABLE IF NOT EXISTS notifications (
    notification_id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    notification_type notification_type NOT NULL DEFAULT 'general',
    related_post_id BIGINT NULL REFERENCES posts(post_id) ON DELETE SET NULL,
    related_user_id BIGINT NULL REFERENCES users(user_id) ON DELETE SET NULL,
    related_event_id BIGINT NULL,
    related_job_id BIGINT NULL,
    content TEXT NULL,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_notifications_user_read ON notifications (user_id, read_at, created_at DESC);

CREATE TABLE IF NOT EXISTS activity_logs (
    log_id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NULL REFERENCES users(user_id) ON DELETE SET NULL,
    action VARCHAR(120) NOT NULL,
    details TEXT NULL,
    severity VARCHAR(20) NOT NULL DEFAULT 'INFO',
    device_fingerprint VARCHAR(120) NULL,
    ip_address VARCHAR(64) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_activity_logs_created ON activity_logs (created_at DESC);
CREATE INDEX IF NOT EXISTS idx_activity_logs_user_created ON activity_logs (user_id, created_at);

CREATE TABLE IF NOT EXISTS device_bans (
    device_ban_id BIGSERIAL PRIMARY KEY,
    device_fingerprint VARCHAR(120) NOT NULL,
    ip_address INET NULL,
    banned_by_admin_id BIGINT NULL REFERENCES users(user_id) ON DELETE SET NULL,
    banned_user_id BIGINT NULL REFERENCES users(user_id) ON DELETE SET NULL,
    reason TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE (device_fingerprint, ip_address)
);
CREATE INDEX IF NOT EXISTS idx_device_bans_fp ON device_bans (device_fingerprint);

CREATE TABLE IF NOT EXISTS moderation_strikes (
    user_id BIGINT PRIMARY KEY REFERENCES users(user_id) ON DELETE CASCADE,
    warning_count INT NOT NULL DEFAULT 0,
    strike_count INT NOT NULL DEFAULT 0,
    shadow_ban_until TIMESTAMP NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);


CREATE TABLE IF NOT EXISTS tech_skills (
    skill_id BIGSERIAL PRIMARY KEY,
    skill_name VARCHAR(120) NOT NULL UNIQUE
);
CREATE TABLE IF NOT EXISTS user_skills (
    user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    skill_id BIGINT NOT NULL REFERENCES tech_skills(skill_id) ON DELETE CASCADE,
    PRIMARY KEY (user_id, skill_id)
);
CREATE TABLE IF NOT EXISTS user_tech_stats (
    user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    skill_name VARCHAR(120) NOT NULL,
    usage_count INT NOT NULL DEFAULT 1,
    PRIMARY KEY (user_id, skill_name)
);

-- ---------------------------------------------------------------- messages & calls
CREATE TABLE IF NOT EXISTS messages (
    message_id BIGSERIAL PRIMARY KEY,
    sender_user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    receiver_user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    content_file_path TEXT NOT NULL,
    is_edited BOOLEAN NOT NULL DEFAULT FALSE,
    edited_at TIMESTAMP NULL,
    read_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_messages_pair ON messages (sender_user_id, receiver_user_id, created_at);
CREATE INDEX IF NOT EXISTS idx_messages_receiver_unread ON messages (receiver_user_id, read_at);

CREATE TABLE IF NOT EXISTS push_subscriptions (
    subscription_id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    endpoint TEXT NOT NULL UNIQUE,
    p256dh TEXT NULL,
    auth TEXT NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ---------------------------------------------------------------- careers & events
CREATE TABLE IF NOT EXISTS jobs (
    job_id BIGSERIAL PRIMARY KEY,
    poster_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    company_name VARCHAR(200) NOT NULL,
    job_title VARCHAR(200) NOT NULL,
    description_file_path TEXT NULL,
    location VARCHAR(200) NULL,
    salary_range VARCHAR(100) NULL,
    application_url TEXT NULL,
    job_type VARCHAR(30) NOT NULL DEFAULT 'full-time',
    status VARCHAR(20) NOT NULL DEFAULT 'open',
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_jobs_created ON jobs (created_at DESC);

CREATE TABLE IF NOT EXISTS job_applications (
    application_id BIGSERIAL PRIMARY KEY,
    job_id BIGINT NOT NULL REFERENCES jobs(job_id) ON DELETE CASCADE,
    applicant_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    cover_letter TEXT NULL,
    resume_path TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE (job_id, applicant_id)
);

CREATE TABLE IF NOT EXISTS events (
    event_id BIGSERIAL PRIMARY KEY,
    creator_user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    start_datetime TIMESTAMP NOT NULL,
    end_datetime TIMESTAMP NULL,
    location_type VARCHAR(20) NOT NULL DEFAULT 'physical',
    location_address TEXT NULL,
    virtual_link TEXT NULL,
    visibility event_visibility NOT NULL DEFAULT 'public',
    rsvp_limit INT NULL,
    rsvp_count INT NOT NULL DEFAULT 0,
    waitlist_count INT NOT NULL DEFAULT 0,
    banner_image_url TEXT NULL,
    has_live_stream BOOLEAN NOT NULL DEFAULT FALSE,
    live_stream_url TEXT NULL,
    comments_enabled BOOLEAN NOT NULL DEFAULT TRUE,
    status event_status NOT NULL DEFAULT 'pending_approval',
    approved_by_admin_id BIGINT NULL REFERENCES users(user_id) ON DELETE SET NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_events_status_start ON events (status, start_datetime);

CREATE TABLE IF NOT EXISTS event_rsvps (
    rsvp_id BIGSERIAL PRIMARY KEY,
    event_id BIGINT NOT NULL REFERENCES events(event_id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    rsvp_status rsvp_status NOT NULL DEFAULT 'attending',
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE (event_id, user_id)
);

CREATE TABLE IF NOT EXISTS announcements (
    announcement_id BIGSERIAL PRIMARY KEY,
    creator_user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    content_file_path TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    approved_by_admin_id BIGINT NULL REFERENCES users(user_id) ON DELETE SET NULL,
    approved_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS live_streams (
    stream_id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL UNIQUE REFERENCES users(user_id) ON DELETE CASCADE,
    stream_key VARCHAR(120) NOT NULL UNIQUE,
    title VARCHAR(255) NULL,
    description TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'offline',
    started_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS success_stories (
    story_id BIGSERIAL PRIMARY KEY,
    alumni_user_id BIGINT NOT NULL UNIQUE REFERENCES users(user_id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    story_content_file_path TEXT NULL,
    featured_image_url TEXT NULL,
    featured_by_admin_id BIGINT NULL REFERENCES users(user_id) ON DELETE SET NULL,
    is_featured BOOLEAN NOT NULL DEFAULT FALSE,
    display_order INT NOT NULL DEFAULT 0,
    view_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS resources (
    resource_id BIGSERIAL PRIMARY KEY,
    uploaded_by BIGINT NULL REFERENCES users(user_id) ON DELETE SET NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category VARCHAR(100) NULL,
    file_url TEXT NULL,
    resource_type VARCHAR(30) NOT NULL DEFAULT 'document',
    status VARCHAR(20) NOT NULL DEFAULT 'approved',
    download_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ---------------------------------------------------------------- renames for databases built from an early 2.1 schema
DO $$ BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'comment_reactions' AND column_name = 'reaction_id') THEN
        ALTER TABLE comment_reactions RENAME COLUMN reaction_id TO comment_reaction_id;
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'scheduled_posts' AND column_name = 'schedule_id') THEN
        ALTER TABLE scheduled_posts RENAME COLUMN schedule_id TO scheduled_id;
    END IF;
END $$;

-- ---------------------------------------------------------------- gamification
CREATE TABLE IF NOT EXISTS badges (
    badge_id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    badge_type VARCHAR(60) NOT NULL,
    earned_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE (user_id, badge_type)
);

-- NOTE: content_payloads, uploaded_assets, reposts, post_views, share links,
-- password_resets, moderation restrictions, mentorship, calls, referrals and
-- portal_settings are owned by the runtime helpers in api/_*.php.
-- scripts/install_db.php runs this file and then every helper, so a fresh
-- database always matches the code exactly.

-- ---------------------------------------------------------------- seed accounts
INSERT INTO users (email, password_hash, role, status) VALUES
 ('admin@rjit.ac.in',   '$2y$12$Mq/KIA2X2oVnMG6XaQDj4uQjSV0S4N1dGf5R8re6bZOjfDrmkmXCO', 'admin',   'active'),
 ('student@rjit.ac.in', '$2y$12$Mq/KIA2X2oVnMG6XaQDj4uQjSV0S4N1dGf5R8re6bZOjfDrmkmXCO', 'student', 'active'),
 ('alumni@example.com', '$2y$12$Mq/KIA2X2oVnMG6XaQDj4uQjSV0S4N1dGf5R8re6bZOjfDrmkmXCO', 'alumni',  'active')
ON CONFLICT (email) DO NOTHING;

INSERT INTO profiles (user_id, full_name, branch, graduation_year, joining_year, current_company, location_city)
SELECT user_id, 'Portal Admin', 'CSE', 2012, 2008, NULL, 'Gwalior' FROM users WHERE email = 'admin@rjit.ac.in'
ON CONFLICT (user_id) DO NOTHING;
INSERT INTO profiles (user_id, full_name, branch, graduation_year, joining_year, current_company, location_city)
SELECT user_id, 'Demo Student', 'CSE', 2028, 2024, NULL, 'Gwalior' FROM users WHERE email = 'student@rjit.ac.in'
ON CONFLICT (user_id) DO NOTHING;
INSERT INTO profiles (user_id, full_name, branch, graduation_year, joining_year, current_company, location_city)
SELECT user_id, 'Demo Alumni', 'CSE', 2020, 2016, 'RJIT Network', 'Bengaluru' FROM users WHERE email = 'alumni@example.com'
ON CONFLICT (user_id) DO NOTHING;
