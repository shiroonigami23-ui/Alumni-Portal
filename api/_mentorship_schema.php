<?php

function ensureMentorshipSchema(PDO $db): void
{
    static $done = false;
    if ($done) {
        return;
    }

    $db->exec("
        CREATE TABLE IF NOT EXISTS mentorship_profiles (
            mentor_user_id BIGINT PRIMARY KEY REFERENCES users(user_id) ON DELETE CASCADE,
            headline TEXT NULL,
            expertise TEXT NULL,
            is_active BOOLEAN NOT NULL DEFAULT FALSE,
            approval_status VARCHAR(20) NOT NULL DEFAULT 'approved',
            approved_by BIGINT NULL REFERENCES users(user_id) ON DELETE SET NULL,
            approved_at TIMESTAMP NULL,
            created_at TIMESTAMP NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP NOT NULL DEFAULT NOW()
        )
    ");
    $db->exec("ALTER TABLE mentorship_profiles ADD COLUMN IF NOT EXISTS approval_status VARCHAR(20) NOT NULL DEFAULT 'approved'");
    $db->exec("ALTER TABLE mentorship_profiles ADD COLUMN IF NOT EXISTS approved_by BIGINT NULL REFERENCES users(user_id) ON DELETE SET NULL");
    $db->exec("ALTER TABLE mentorship_profiles ADD COLUMN IF NOT EXISTS approved_at TIMESTAMP NULL");
    $db->exec("ALTER TABLE mentorship_profiles ALTER COLUMN is_active SET DEFAULT FALSE");

    $db->exec("
        CREATE TABLE IF NOT EXISTS mentorship_requests (
            request_id BIGSERIAL PRIMARY KEY,
            mentee_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
            mentor_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
            message TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','accepted','rejected')),
            created_at TIMESTAMP NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
            UNIQUE (mentee_id, mentor_id)
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS mentorship_groups (
            group_id BIGSERIAL PRIMARY KEY,
            mentor_user_id BIGINT NOT NULL UNIQUE REFERENCES users(user_id) ON DELETE CASCADE,
            admin_user_id BIGINT NULL REFERENCES users(user_id) ON DELETE SET NULL,
            title TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP NOT NULL DEFAULT NOW()
        )
    ");
    $db->exec("ALTER TABLE mentorship_groups ADD COLUMN IF NOT EXISTS admin_user_id BIGINT NULL REFERENCES users(user_id) ON DELETE SET NULL");

    $db->exec("
        CREATE TABLE IF NOT EXISTS mentorship_group_members (
            group_id BIGINT NOT NULL REFERENCES mentorship_groups(group_id) ON DELETE CASCADE,
            user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
            member_role VARCHAR(20) NOT NULL DEFAULT 'member',
            joined_at TIMESTAMP NOT NULL DEFAULT NOW(),
            PRIMARY KEY (group_id, user_id)
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS mentorship_matches (
            match_id BIGSERIAL PRIMARY KEY,
            mentor_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
            mentee_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
            group_id BIGINT NULL REFERENCES mentorship_groups(group_id) ON DELETE SET NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active','left','removed')),
            joined_at TIMESTAMP NOT NULL DEFAULT NOW(),
            ended_at TIMESTAMP NULL,
            ended_by BIGINT NULL REFERENCES users(user_id) ON DELETE SET NULL,
            ended_reason TEXT NULL
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS mentorship_group_bans (
            group_id BIGINT NOT NULL REFERENCES mentorship_groups(group_id) ON DELETE CASCADE,
            user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
            banned_by BIGINT NULL REFERENCES users(user_id) ON DELETE SET NULL,
            banned_until TIMESTAMP NULL,
            is_permanent BOOLEAN NOT NULL DEFAULT FALSE,
            reason TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
            PRIMARY KEY (group_id, user_id)
        )
    ");

    $db->exec("CREATE INDEX IF NOT EXISTS idx_mentorship_profiles_active ON mentorship_profiles(is_active)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_mentorship_profiles_status ON mentorship_profiles(approval_status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_mentorship_requests_mentor ON mentorship_requests(mentor_id, status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_mentorship_requests_mentee ON mentorship_requests(mentee_id, status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_mentorship_group_members_user ON mentorship_group_members(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_mentorship_matches_mentor ON mentorship_matches(mentor_id, status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_mentorship_matches_mentee ON mentorship_matches(mentee_id, status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_mentorship_group_bans_user ON mentorship_group_bans(user_id)");
    $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_mentorship_active_mentee_unique ON mentorship_matches(mentee_id) WHERE status = 'active'");

    $db->exec("
        UPDATE mentorship_profiles
        SET approval_status = 'approved'
        WHERE is_active = TRUE
          AND COALESCE(NULLIF(TRIM(approval_status), ''), 'approved') <> 'approved'
    ");
    $db->exec("
        UPDATE mentorship_profiles
        SET is_active = FALSE
        WHERE approval_status <> 'approved'
          AND is_active = TRUE
    ");
    $db->exec("
        UPDATE mentorship_groups
        SET admin_user_id = mentor_user_id
        WHERE admin_user_id IS NULL
    ");

    $done = true;
}

function getUserRole(PDO $db, int $userId): string
{
    $stmt = $db->prepare("SELECT role FROM users WHERE user_id = :uid LIMIT 1");
    $stmt->execute([':uid' => $userId]);
    return strtolower((string)($stmt->fetchColumn() ?: ''));
}

function canReviewMentorApplications(string $role): bool
{
    return in_array($role, ['faculty', 'admin'], true);
}

function canSelfActivateAsMentor(string $role): bool
{
    return in_array($role, ['faculty', 'admin'], true);
}

function canApplyForMentorship(string $role): bool
{
    return in_array($role, ['faculty', 'admin', 'alumni'], true);
}

function canRequestMentorship(string $role): bool
{
    return in_array($role, ['student', 'alumni'], true);
}

function getMentorProfile(PDO $db, int $userId): ?array
{
    $stmt = $db->prepare("
        SELECT mentor_user_id, headline, expertise, is_active, approval_status, approved_by, approved_at, created_at, updated_at
        FROM mentorship_profiles
        WHERE mentor_user_id = :uid
        LIMIT 1
    ");
    $stmt->execute([':uid' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function ensureMentorGroup(PDO $db, int $mentorUserId): int
{
    $stmt = $db->prepare("
        SELECT g.group_id
        FROM mentorship_groups g
        WHERE g.mentor_user_id = :mentor_id
        LIMIT 1
    ");
    $stmt->execute([':mentor_id' => $mentorUserId]);
    $existing = $stmt->fetchColumn();
    if ($existing) {
        $groupId = (int)$existing;
        $db->prepare("
            UPDATE mentorship_groups
            SET admin_user_id = COALESCE(admin_user_id, mentor_user_id),
                updated_at = NOW()
            WHERE group_id = :gid
        ")->execute([':gid' => $groupId]);
    } else {
        $titleStmt = $db->prepare("
            SELECT COALESCE(NULLIF(TRIM(p.full_name), ''), split_part(u.email, '@', 1)) AS display_name
            FROM users u
            LEFT JOIN profiles p ON p.user_id = u.user_id
            WHERE u.user_id = :mentor_id
            LIMIT 1
        ");
        $titleStmt->execute([':mentor_id' => $mentorUserId]);
        $displayName = (string)($titleStmt->fetchColumn() ?: 'Mentor');
        $groupTitle = $displayName . "'s Mentor Group";

        $insert = $db->prepare("
            INSERT INTO mentorship_groups (mentor_user_id, admin_user_id, title, updated_at)
            VALUES (:mentor_id, :admin_user_id, :title, NOW())
            RETURNING group_id
        ");
        $insert->execute([
            ':mentor_id' => $mentorUserId,
            ':admin_user_id' => $mentorUserId,
            ':title' => $groupTitle
        ]);
        $groupId = (int)$insert->fetchColumn();
    }

    $memberStmt = $db->prepare("
        INSERT INTO mentorship_group_members (group_id, user_id, member_role)
        VALUES (:group_id, :user_id, 'admin')
        ON CONFLICT (group_id, user_id)
        DO UPDATE SET member_role = 'admin'
    ");
    $memberStmt->execute([
        ':group_id' => $groupId,
        ':user_id' => $mentorUserId
    ]);

    return $groupId;
}

function getCurrentActiveMatch(PDO $db, int $menteeId): ?array
{
    $stmt = $db->prepare("
        SELECT
            mm.match_id,
            mm.mentor_id,
            mm.mentee_id,
            mm.group_id,
            mm.joined_at,
            COALESCE(NULLIF(TRIM(p.full_name), ''), split_part(u.email, '@', 1)) AS mentor_name,
            u.role AS mentor_role
        FROM mentorship_matches mm
        JOIN users u ON u.user_id = mm.mentor_id
        LEFT JOIN profiles p ON p.user_id = u.user_id
        WHERE mm.mentee_id = :uid
          AND mm.status = 'active'
        ORDER BY mm.joined_at DESC, mm.match_id DESC
        LIMIT 1
    ");
    $stmt->execute([':uid' => $menteeId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function getActiveGroupBan(PDO $db, int $groupId, int $userId): ?array
{
    $stmt = $db->prepare("
        SELECT group_id, user_id, banned_by, banned_until, is_permanent, reason, created_at, updated_at
        FROM mentorship_group_bans
        WHERE group_id = :gid
          AND user_id = :uid
          AND (is_permanent = TRUE OR banned_until > NOW())
        LIMIT 1
    ");
    $stmt->execute([
        ':gid' => $groupId,
        ':uid' => $userId
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function userCanManageMentorGroup(PDO $db, int $groupId, int $userId): bool
{
    $stmt = $db->prepare("
        SELECT 1
        FROM mentorship_groups g
        LEFT JOIN mentorship_group_members gm
          ON gm.group_id = g.group_id
         AND gm.user_id = :uid
        WHERE g.group_id = :gid
          AND (
                g.admin_user_id = :uid
             OR gm.member_role = 'admin'
          )
        LIMIT 1
    ");
    $stmt->execute([
        ':gid' => $groupId,
        ':uid' => $userId
    ]);
    return (bool)$stmt->fetchColumn();
}

function getNextGroupAdminCandidate(PDO $db, int $groupId, int $excludeUserId): ?array
{
    $stmt = $db->prepare("
        SELECT gm.user_id
        FROM mentorship_group_members gm
        LEFT JOIN mentorship_group_bans gb
          ON gb.group_id = gm.group_id
         AND gb.user_id = gm.user_id
         AND (gb.is_permanent = TRUE OR gb.banned_until > NOW())
        WHERE gm.group_id = :gid
          AND gm.user_id <> :uid
          AND gb.user_id IS NULL
        ORDER BY
            CASE WHEN gm.member_role = 'admin' THEN 0 ELSE 1 END,
            gm.joined_at ASC,
            gm.user_id ASC
        LIMIT 1
    ");
    $stmt->execute([
        ':gid' => $groupId,
        ':uid' => $excludeUserId
    ]);
    $userId = $stmt->fetchColumn();
    if (!$userId) {
        return null;
    }
    return ['user_id' => (int)$userId];
}

function transferMentorGroupAdmin(PDO $db, int $groupId, int $newAdminUserId): void
{
    $db->prepare("
        UPDATE mentorship_groups
        SET admin_user_id = :new_admin,
            updated_at = NOW()
        WHERE group_id = :gid
    ")->execute([
        ':new_admin' => $newAdminUserId,
        ':gid' => $groupId
    ]);

    $db->prepare("
        UPDATE mentorship_group_members
        SET member_role = CASE WHEN user_id = :new_admin THEN 'admin' ELSE 'member' END
        WHERE group_id = :gid
    ")->execute([
        ':new_admin' => $newAdminUserId,
        ':gid' => $groupId
    ]);
}
