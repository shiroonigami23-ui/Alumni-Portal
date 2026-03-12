-- Performance indexes for moderation, profile visibility, and feed scaling.

CREATE INDEX IF NOT EXISTS idx_reports_user_pending
ON reports (reported_user_id, status, reporter_user_id)
WHERE reported_user_id IS NOT NULL;

CREATE UNIQUE INDEX IF NOT EXISTS idx_reports_unique_user_report
ON reports (reported_user_id, reporter_user_id)
WHERE reported_user_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_profiles_public_alumni_photos
ON profiles (is_private, updated_at DESC, user_id)
WHERE COALESCE(profile_picture_url, '') <> '';

CREATE INDEX IF NOT EXISTS idx_users_role_status
ON users (role, status);

