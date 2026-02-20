-- Mentorship feature schema
-- Run this once per environment (local, staging, prod).

CREATE TABLE IF NOT EXISTS mentorship_requests (
    request_id BIGSERIAL PRIMARY KEY,
    mentee_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    mentor_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    message TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'accepted', 'rejected')),
    created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (mentee_id, mentor_id)
);

CREATE INDEX IF NOT EXISTS idx_mentorship_requests_mentor_status
    ON mentorship_requests (mentor_id, status);

CREATE INDEX IF NOT EXISTS idx_mentorship_requests_mentee
    ON mentorship_requests (mentee_id);
