-- Messaging feature upgrade: read ticks, edit/delete controls, call logs

ALTER TABLE messages ADD COLUMN IF NOT EXISTS edited_at TIMESTAMP NULL;
ALTER TABLE messages ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL;

CREATE TABLE IF NOT EXISTS calls (
    call_id BIGSERIAL PRIMARY KEY,
    initiator_user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    receiver_user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    call_type VARCHAR(10) NOT NULL CHECK (call_type IN ('audio', 'video')),
    room_code VARCHAR(255) NOT NULL,
    room_url TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

