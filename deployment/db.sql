-- RJIT Alumni Portal bootstrap schema (MySQL 8+)
-- Import:
--   mysql -u root -p < deployment/db.sql

CREATE DATABASE IF NOT EXISTS alumni_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE alumni_portal;

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS users (
    user_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'student',
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    can_post TINYINT(1) NOT NULL DEFAULT 1,
    is_moderator TINYINT(1) NOT NULL DEFAULT 0,
    total_posts INT NOT NULL DEFAULT 0,
    total_likes_received INT NOT NULL DEFAULT 0,
    login_streak INT NOT NULL DEFAULT 0,
    suspension_expires_at TIMESTAMP NULL DEFAULT NULL,
    last_login TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role_status (role, status)
);

CREATE TABLE IF NOT EXISTS profiles (
    profile_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
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
    is_private TINYINT(1) NOT NULL DEFAULT 0,
    show_email TINYINT(1) NOT NULL DEFAULT 1,
    show_contact TINYINT(1) NOT NULL DEFAULT 0,
    roll_number VARCHAR(80) NULL,
    year_of_study VARCHAR(80) NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_profiles_branch (branch),
    INDEX idx_profiles_company (current_company)
);

CREATE TABLE IF NOT EXISTS students (
    student_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    roll_number VARCHAR(80) NOT NULL,
    course VARCHAR(100) NOT NULL,
    branch VARCHAR(150) NOT NULL,
    graduation_year INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS sessions (
    session_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(64) NULL,
    user_agent TEXT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_sessions_user (user_id),
    INDEX idx_sessions_expiry (expires_at)
);

CREATE TABLE IF NOT EXISTS posts (
    post_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NULL,
    content_file_path TEXT NULL,
    post_type VARCHAR(50) NOT NULL DEFAULT 'text',
    status VARCHAR(30) NOT NULL DEFAULT 'published',
    moderation_status VARCHAR(30) NOT NULL DEFAULT 'approved',
    visibility_scope VARCHAR(32) NOT NULL DEFAULT 'all',
    reviewed_by_user_id BIGINT UNSIGNED NULL,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    review_note TEXT NULL,
    thumbnail_url TEXT NULL,
    comments_enabled TINYINT(1) NOT NULL DEFAULT 1,
    is_pinned TINYINT(1) NOT NULL DEFAULT 0,
    is_edited TINYINT(1) NOT NULL DEFAULT 0,
    last_edited_at TIMESTAMP NULL DEFAULT NULL,
    reaction_count INT NOT NULL DEFAULT 0,
    comment_count INT NOT NULL DEFAULT 0,
    view_count INT NOT NULL DEFAULT 0,
    share_count INT NOT NULL DEFAULT 0,
    repost_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_posts_status_created (status, created_at),
    INDEX idx_posts_moderation_status (moderation_status),
    INDEX idx_posts_visibility_scope (visibility_scope)
);

CREATE TABLE IF NOT EXISTS comments (
    comment_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    parent_comment_id BIGINT UNSIGNED NULL,
    content_file_path TEXT NOT NULL,
    reaction_count INT NOT NULL DEFAULT 0,
    report_count INT NOT NULL DEFAULT 0,
    is_edited TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (parent_comment_id) REFERENCES comments(comment_id) ON DELETE CASCADE,
    INDEX idx_comments_post_created (post_id, created_at)
);

CREATE TABLE IF NOT EXISTS likes (
    like_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY uq_likes_post_user (post_id, user_id)
);

CREATE TABLE IF NOT EXISTS reposts (
    repost_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY uq_reposts_post_user (post_id, user_id)
);

CREATE TABLE IF NOT EXISTS pinned_posts (
    pin_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    post_id BIGINT UNSIGNED NOT NULL,
    pin_order INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE,
    UNIQUE KEY uq_pinned_post_user (user_id, post_id)
);

CREATE TABLE IF NOT EXISTS notifications (
    notification_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    notification_type VARCHAR(50) NOT NULL DEFAULT 'general',
    related_post_id BIGINT UNSIGNED NULL,
    related_user_id BIGINT UNSIGNED NULL,
    content TEXT NULL,
    read_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (related_post_id) REFERENCES posts(post_id) ON DELETE SET NULL,
    FOREIGN KEY (related_user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_notifications_user_read (user_id, read_at)
);

CREATE TABLE IF NOT EXISTS connections (
    connection_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    requester_user_id BIGINT UNSIGNED NOT NULL,
    addressee_user_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'accepted',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    accepted_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (requester_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (addressee_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY uq_connection_pair (requester_user_id, addressee_user_id),
    INDEX idx_connections_status (status)
);

CREATE TABLE IF NOT EXISTS reports (
    report_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    reporter_user_id BIGINT UNSIGNED NOT NULL,
    reported_user_id BIGINT UNSIGNED NULL,
    reported_post_id BIGINT UNSIGNED NULL,
    reported_comment_id BIGINT UNSIGNED NULL,
    reason VARCHAR(255) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reporter_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (reported_user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (reported_post_id) REFERENCES posts(post_id) ON DELETE SET NULL,
    FOREIGN KEY (reported_comment_id) REFERENCES comments(comment_id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS blocks (
    block_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    blocker_user_id BIGINT UNSIGNED NOT NULL,
    blocked_user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (blocker_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (blocked_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY uq_blocks_pair (blocker_user_id, blocked_user_id)
);

CREATE TABLE IF NOT EXISTS activity_logs (
    log_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    details TEXT NULL,
    severity VARCHAR(20) NOT NULL DEFAULT 'INFO',
    device_fingerprint VARCHAR(120) NULL,
    ip_address VARCHAR(64) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_activity_logs_user_created (user_id, created_at)
);

CREATE TABLE IF NOT EXISTS device_bans (
    device_ban_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    device_fingerprint VARCHAR(120) NOT NULL,
    ip_address VARCHAR(64) NOT NULL,
    banned_by_admin_id BIGINT UNSIGNED NULL,
    banned_user_id BIGINT UNSIGNED NULL,
    reason TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (banned_by_admin_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (banned_user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    UNIQUE KEY uq_device_ban_fingerprint_ip (device_fingerprint, ip_address),
    INDEX idx_device_bans_banned_user_id (banned_user_id)
);

CREATE TABLE IF NOT EXISTS moderation_strikes (
    user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    warning_count INT NOT NULL DEFAULT 0,
    strike_count INT NOT NULL DEFAULT 0,
    shadow_ban_until TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS user_moderation_restrictions (
    user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    posting_ban_until TIMESTAMP NULL DEFAULT NULL,
    posting_ban_reason TEXT NULL,
    messaging_ban_until TIMESTAMP NULL DEFAULT NULL,
    messaging_ban_reason TEXT NULL,
    updated_by_admin_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by_admin_id) REFERENCES users(user_id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS content_payloads (
    content_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(120) NOT NULL UNIQUE,
    owner_user_id BIGINT UNSIGNED NULL,
    scope VARCHAR(60) NOT NULL DEFAULT 'generic',
    payload JSON NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS content_assets (
    asset_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(120) NOT NULL UNIQUE,
    owner_user_id BIGINT UNSIGNED NULL,
    scope VARCHAR(60) NOT NULL DEFAULT 'generic',
    asset_kind VARCHAR(20) NOT NULL DEFAULT 'file',
    original_name VARCHAR(255) NULL,
    mime_type VARCHAR(120) NULL,
    file_ext VARCHAR(20) NULL,
    file_size BIGINT NOT NULL DEFAULT 0,
    binary_data LONGBLOB NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS password_resets (
    reset_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NULL DEFAULT NULL,
    used_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_password_resets_email (email),
    INDEX idx_password_resets_token_hash (token_hash)
);

CREATE TABLE IF NOT EXISTS invite_tokens (
    token_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(120) NOT NULL UNIQUE,
    email VARCHAR(255) NULL,
    usage_limit INT NOT NULL DEFAULT 1,
    used_count INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by_user_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- minimal seed accounts (password: ChangeMe@123)
INSERT INTO users (email, password_hash, role, status, can_post, is_moderator)
VALUES
('admin@rjit.ac.in', '$2y$12$Mq/KIA2X2oVnMG6XaQDj4uQjSV0S4N1dGf5R8re6bZOjfDrmkmXCO', 'admin', 'active', 1, 0),
('student@rjit.ac.in', '$2y$12$Mq/KIA2X2oVnMG6XaQDj4uQjSV0S4N1dGf5R8re6bZOjfDrmkmXCO', 'student', 'active', 1, 0),
('alumni@example.com', '$2y$12$Mq/KIA2X2oVnMG6XaQDj4uQjSV0S4N1dGf5R8re6bZOjfDrmkmXCO', 'alumni', 'active', 1, 0)
ON DUPLICATE KEY UPDATE email = VALUES(email);

INSERT INTO profiles (user_id, full_name, branch, graduation_year, joining_year, contact_number)
SELECT user_id, 'Portal Admin', 'CSE', 2012, 2008, '+91-9000000000' FROM users WHERE email = 'admin@rjit.ac.in'
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name);

INSERT INTO profiles (user_id, full_name, branch, graduation_year, joining_year, contact_number)
SELECT user_id, 'Demo Student', 'CSE', 2028, 2024, '+91-9000000001' FROM users WHERE email = 'student@rjit.ac.in'
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name);

INSERT INTO profiles (user_id, full_name, branch, graduation_year, joining_year, current_company, contact_number)
SELECT user_id, 'Demo Alumni', 'CSE', 2020, 2016, 'RJIT Network', '+91-9000000002' FROM users WHERE email = 'alumni@example.com'
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name);
