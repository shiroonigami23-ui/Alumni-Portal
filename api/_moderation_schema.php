<?php

require_once __DIR__ . '/../config/DbCompat.php';

function ensure_user_moderation_schema(PDO $db): void
{
    static $done = false;
    if ($done) {
        return;
    }

    if (db_is_mysql($db)) {
        $db->exec("
            CREATE TABLE IF NOT EXISTS user_moderation_restrictions (
                user_id BIGINT PRIMARY KEY,
                posting_ban_until TIMESTAMP NULL,
                posting_ban_reason TEXT NULL,
                messaging_ban_until TIMESTAMP NULL,
                messaging_ban_reason TEXT NULL,
                updated_by_admin_id BIGINT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");
    } else {
        $db->exec("
            CREATE TABLE IF NOT EXISTS user_moderation_restrictions (
                user_id BIGINT PRIMARY KEY REFERENCES users(user_id) ON DELETE CASCADE,
                posting_ban_until TIMESTAMP NULL,
                posting_ban_reason TEXT NULL,
                messaging_ban_until TIMESTAMP NULL,
                messaging_ban_reason TEXT NULL,
                updated_by_admin_id BIGINT NULL REFERENCES users(user_id) ON DELETE SET NULL,
                created_at TIMESTAMP NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMP NOT NULL DEFAULT NOW()
            )
        ");
    }

    if (db_table_exists($db, 'device_bans') && !db_column_exists($db, 'device_bans', 'banned_user_id')) {
        $db->exec("ALTER TABLE device_bans ADD COLUMN banned_user_id BIGINT NULL");
    }
    try {
        if (db_is_mysql($db)) {
            $db->exec("CREATE INDEX idx_device_bans_banned_user_id ON device_bans(banned_user_id)");
        } else {
            $db->exec("CREATE INDEX IF NOT EXISTS idx_device_bans_banned_user_id ON device_bans(banned_user_id)");
        }
    } catch (Throwable $ignored) {
    }

    $done = true;
}

function moderation_get_user_role(PDO $db, int $userId): string
{
    $stmt = $db->prepare("SELECT role FROM users WHERE user_id = :uid LIMIT 1");
    $stmt->execute(['uid' => $userId]);
    return (string)($stmt->fetchColumn() ?: '');
}

function moderation_get_user_email(PDO $db, int $userId): string
{
    $stmt = $db->prepare("SELECT email FROM users WHERE user_id = :uid LIMIT 1");
    $stmt->execute(['uid' => $userId]);
    return (string)($stmt->fetchColumn() ?: '');
}

function moderation_is_profile_private(PDO $db, int $userId): bool
{
    $stmt = $db->prepare("
        SELECT CASE WHEN u.role = 'student' THEN FALSE ELSE COALESCE(p.is_private, FALSE) END AS is_private
        FROM users u
        LEFT JOIN profiles p ON p.user_id = u.user_id
        WHERE u.user_id = :uid
        LIMIT 1
    ");
    $stmt->execute(['uid' => $userId]);
    return (bool)$stmt->fetchColumn();
}

function moderation_get_restrictions(PDO $db, int $userId): array
{
    ensure_user_moderation_schema($db);

    $role = moderation_get_user_role($db, $userId);
    if ($role === 'admin') {
        return [
            'role' => 'admin',
            'posting_blocked' => false,
            'posting_ban_until' => null,
            'posting_ban_reason' => null,
            'messaging_blocked' => false,
            'messaging_ban_until' => null,
            'messaging_ban_reason' => null,
        ];
    }

    $stmt = $db->prepare("
        SELECT posting_ban_until, posting_ban_reason, messaging_ban_until, messaging_ban_reason
        FROM user_moderation_restrictions
        WHERE user_id = :uid
        LIMIT 1
    ");
    $stmt->execute(['uid' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $now = time();
    $postingUntil = !empty($row['posting_ban_until']) ? strtotime((string)$row['posting_ban_until']) : false;
    $messagingUntil = !empty($row['messaging_ban_until']) ? strtotime((string)$row['messaging_ban_until']) : false;

    return [
        'role' => $role,
        'posting_blocked' => ($postingUntil !== false && $postingUntil > $now),
        'posting_ban_until' => $row['posting_ban_until'] ?? null,
        'posting_ban_reason' => $row['posting_ban_reason'] ?? null,
        'messaging_blocked' => ($messagingUntil !== false && $messagingUntil > $now),
        'messaging_ban_until' => $row['messaging_ban_until'] ?? null,
        'messaging_ban_reason' => $row['messaging_ban_reason'] ?? null,
    ];
}

function moderation_assert_posting_allowed(PDO $db, int $userId, string $message = 'Posting is currently restricted for this account.'): void
{
    $state = moderation_get_restrictions($db, $userId);
    if (!$state['posting_blocked']) {
        return;
    }

    http_response_code(403);
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => $message,
        'ban_until' => $state['posting_ban_until'],
        'reason' => $state['posting_ban_reason'],
    ]);
    exit;
}

function moderation_assert_messaging_allowed(PDO $db, int $userId, string $message = 'Messaging is currently restricted for this account.'): void
{
    $state = moderation_get_restrictions($db, $userId);
    if (!$state['messaging_blocked']) {
        return;
    }

    http_response_code(403);
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => $message,
        'ban_until' => $state['messaging_ban_until'],
        'reason' => $state['messaging_ban_reason'],
    ]);
    exit;
}

function moderation_set_restriction(PDO $db, int $targetUserId, string $kind, ?string $until, string $reason, int $adminUserId): void
{
    ensure_user_moderation_schema($db);

    if (!in_array($kind, ['posting', 'messaging'], true)) {
        throw new InvalidArgumentException('Unsupported moderation restriction kind.');
    }

    $columnUntil = $kind . '_ban_until';
    $columnReason = $kind . '_ban_reason';

    $existsStmt = $db->prepare("SELECT 1 FROM user_moderation_restrictions WHERE user_id = :uid LIMIT 1");
    $existsStmt->execute(['uid' => $targetUserId]);
    $exists = (bool)$existsStmt->fetchColumn();

    if ($exists) {
        $sql = "
            UPDATE user_moderation_restrictions
            SET {$columnUntil} = :until,
                {$columnReason} = :reason,
                updated_by_admin_id = :aid,
                updated_at = CURRENT_TIMESTAMP
            WHERE user_id = :uid
        ";
    } else {
        $sql = "
            INSERT INTO user_moderation_restrictions (user_id, {$columnUntil}, {$columnReason}, updated_by_admin_id, updated_at)
            VALUES (:uid, :until, :reason, :aid, CURRENT_TIMESTAMP)
        ";
    }
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':uid', $targetUserId, PDO::PARAM_INT);
    if ($until === null || $until === '') {
        $stmt->bindValue(':until', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':until', $until, PDO::PARAM_STR);
    }
    $stmt->bindValue(':reason', $reason, PDO::PARAM_STR);
    $stmt->bindValue(':aid', $adminUserId, PDO::PARAM_INT);
    $stmt->execute();
}

function moderation_clear_restriction(PDO $db, int $targetUserId, string $kind, int $adminUserId): void
{
    ensure_user_moderation_schema($db);

    if (!in_array($kind, ['posting', 'messaging'], true)) {
        throw new InvalidArgumentException('Unsupported moderation restriction kind.');
    }

    $columnUntil = $kind . '_ban_until';
    $columnReason = $kind . '_ban_reason';
    $existsStmt = $db->prepare("SELECT 1 FROM user_moderation_restrictions WHERE user_id = :uid LIMIT 1");
    $existsStmt->execute(['uid' => $targetUserId]);
    $exists = (bool)$existsStmt->fetchColumn();

    if ($exists) {
        $stmt = $db->prepare("
            UPDATE user_moderation_restrictions
            SET {$columnUntil} = NULL,
                {$columnReason} = NULL,
                updated_by_admin_id = :aid,
                updated_at = CURRENT_TIMESTAMP
            WHERE user_id = :uid
        ");
        $stmt->execute([
            'uid' => $targetUserId,
            'aid' => $adminUserId,
        ]);
        return;
    }

    $stmt = $db->prepare("
        INSERT INTO user_moderation_restrictions (user_id, updated_by_admin_id, updated_at)
        VALUES (:uid, :aid, CURRENT_TIMESTAMP)
    ");
    $stmt->execute([
        'uid' => $targetUserId,
        'aid' => $adminUserId,
    ]);
}
