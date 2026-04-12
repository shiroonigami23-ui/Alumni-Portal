<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once __DIR__ . '/_moderation_schema.php';
include_once __DIR__ . '/_community_schema.php';
include_once '../config/DbCompat.php';

function respond_action(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit();
}

function require_admin_actor(PDO $db, int $userId): void
{
    if (moderation_get_user_role($db, $userId) !== 'admin') {
        respond_action(['success' => false, 'message' => 'Admin access only.'], 403);
    }
}

function collect_user_device_sources(PDO $db, int $userId): array
{
    $stmt = $db->prepare("
        SELECT device_fingerprint, ip_address AS ip_address, user_agent
        FROM sessions
        WHERE user_id = :uid
        UNION
        SELECT device_fingerprint, ip_address AS ip_address, user_agent
        FROM activity_logs
        WHERE user_id = :uid
    ");
    $stmt->execute(['uid' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

$database = new Database();
$db = $database->getConnection();
if (!$db) {
    respond_action(['success' => false, 'message' => 'Database unavailable.'], 503);
}
$auth = new Auth($db);
ensure_user_moderation_schema($db);
ensure_community_schema($db);

try {
    $adminId = (int)$auth->validateRequest();
    require_admin_actor($db, $adminId);

    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $action = trim((string)($data['action'] ?? ''));
    $targetUserId = (int)($data['target_user_id'] ?? 0);
    $reason = trim((string)($data['reason'] ?? 'Admin moderation action.'));
    $durationHours = max(1, min(24 * 365, (int)($data['duration_hours'] ?? 168)));
    $newPassword = (string)($data['new_password'] ?? '');

    if ($targetUserId <= 0) {
        respond_action(['success' => false, 'message' => 'target_user_id is required.'], 400);
    }

    $targetStmt = $db->prepare("SELECT user_id, email, role, status FROM users WHERE user_id = :uid LIMIT 1");
    $targetStmt->execute(['uid' => $targetUserId]);
    $target = $targetStmt->fetch(PDO::FETCH_ASSOC);
    if (!$target) {
        respond_action(['success' => false, 'message' => 'Target user not found.'], 404);
    }
    if ((int)$target['user_id'] === $adminId && $action !== 'reset_password') {
        respond_action(['success' => false, 'message' => 'Admin cannot apply this action to self.'], 409);
    }

    switch ($action) {
        case 'ban_user':
            $db->beginTransaction();
            $db->prepare("UPDATE users SET status = 'banned', suspension_expires_at = NULL WHERE user_id = :uid")
               ->execute(['uid' => $targetUserId]);

            $seen = [];
            if (db_is_mysql($db)) {
                $insertBan = $db->prepare("
                    INSERT INTO device_bans (device_fingerprint, ip_address, banned_by_admin_id, banned_user_id, reason)
                    VALUES (:fp, :ip, :aid, :buid, :reason)
                    ON DUPLICATE KEY UPDATE
                        banned_by_admin_id = VALUES(banned_by_admin_id),
                        banned_user_id = VALUES(banned_user_id),
                        reason = VALUES(reason)
                ");
            } else {
                $insertBan = $db->prepare("
                    INSERT INTO device_bans (device_fingerprint, ip_address, banned_by_admin_id, banned_user_id, reason)
                    VALUES (:fp, CAST(:ip AS inet), :aid, :buid, :reason)
                    ON CONFLICT (device_fingerprint, ip_address) DO NOTHING
                ");
            }
            foreach (collect_user_device_sources($db, $targetUserId) as $row) {
                $ip = trim((string)($row['ip_address'] ?? ''));
                if ($ip === '') {
                    continue;
                }
                $fp = trim((string)($row['device_fingerprint'] ?? ''));
                if ($fp === '') {
                    $ua = (string)($row['user_agent'] ?? '');
                    $fp = substr(hash('sha256', $ua . '|' . $ip), 0, 120);
                }
                $key = $fp . '|' . $ip;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $insertBan->execute([
                    'fp' => $fp,
                    'ip' => $ip,
                    'aid' => $adminId,
                    'buid' => $targetUserId,
                    'reason' => $reason,
                ]);
            }
            $db->prepare("DELETE FROM sessions WHERE user_id = :uid")->execute(['uid' => $targetUserId]);
            $auth->logAction($adminId, 'BAN_USER', "Banned user {$targetUserId}: {$reason}");
            $db->commit();
            respond_action(['success' => true, 'message' => 'User banned and active devices blacklisted where available.']);

        case 'unban_user':
            $db->beginTransaction();
            $db->prepare("UPDATE users SET status = 'active', suspension_expires_at = NULL WHERE user_id = :uid")
               ->execute(['uid' => $targetUserId]);
            $db->prepare("DELETE FROM device_bans WHERE banned_user_id = :uid")->execute(['uid' => $targetUserId]);
            $auth->logAction($adminId, 'UNBAN_USER', "Unbanned user {$targetUserId}");
            $db->commit();
            respond_action(['success' => true, 'message' => 'User unbanned.']);

        case 'restrict_posting':
            $until = (new DateTimeImmutable("+{$durationHours} hours"))->format('Y-m-d H:i:s');
            moderation_set_restriction($db, $targetUserId, 'posting', $until, $reason, $adminId);
            $auth->logAction($adminId, 'RESTRICT_POSTING', "Posting restricted for user {$targetUserId} until {$until}");
            respond_action(['success' => true, 'message' => 'Posting restricted.', 'ban_until' => $until]);

        case 'restrict_messaging':
            $until = (new DateTimeImmutable("+{$durationHours} hours"))->format('Y-m-d H:i:s');
            moderation_set_restriction($db, $targetUserId, 'messaging', $until, $reason, $adminId);
            $auth->logAction($adminId, 'RESTRICT_MESSAGING', "Messaging restricted for user {$targetUserId} until {$until}");
            respond_action(['success' => true, 'message' => 'Messaging restricted.', 'ban_until' => $until]);

        case 'shadow_ban':
            $until = (new DateTimeImmutable("+{$durationHours} hours"))->format('Y-m-d H:i:s');
            moderation_set_restriction($db, $targetUserId, 'posting', $until, $reason, $adminId);
            moderation_set_restriction($db, $targetUserId, 'messaging', $until, $reason, $adminId);
            if (db_is_mysql($db)) {
                $db->prepare("
                    INSERT INTO moderation_strikes (user_id, warning_count, strike_count, shadow_ban_until)
                    VALUES (:uid, 0, 1, :until)
                    ON DUPLICATE KEY UPDATE shadow_ban_until = VALUES(shadow_ban_until)
                ")->execute(['uid' => $targetUserId, 'until' => $until]);
            } else {
                $db->prepare("
                    INSERT INTO moderation_strikes (user_id, warning_count, strike_count, shadow_ban_until)
                    VALUES (:uid, 0, 1, :until)
                    ON CONFLICT (user_id) DO UPDATE SET shadow_ban_until = EXCLUDED.shadow_ban_until
                ")->execute(['uid' => $targetUserId, 'until' => $until]);
            }
            $auth->logAction($adminId, 'SHADOW_BAN', "Shadow banned user {$targetUserId} until {$until}");
            respond_action(['success' => true, 'message' => 'User shadow-banned from posting and messaging.', 'ban_until' => $until]);

        case 'lift_posting_restriction':
            moderation_clear_restriction($db, $targetUserId, 'posting', $adminId);
            $auth->logAction($adminId, 'LIFT_POSTING_RESTRICTION', "Posting restriction lifted for {$targetUserId}");
            respond_action(['success' => true, 'message' => 'Posting restriction lifted.']);

        case 'lift_messaging_restriction':
            moderation_clear_restriction($db, $targetUserId, 'messaging', $adminId);
            $auth->logAction($adminId, 'LIFT_MESSAGING_RESTRICTION', "Messaging restriction lifted for {$targetUserId}");
            respond_action(['success' => true, 'message' => 'Messaging restriction lifted.']);

        case 'lift_shadow_ban':
            moderation_clear_restriction($db, $targetUserId, 'posting', $adminId);
            moderation_clear_restriction($db, $targetUserId, 'messaging', $adminId);
            if (db_is_mysql($db)) {
                $db->prepare("
                    INSERT INTO moderation_strikes (user_id, warning_count, strike_count, shadow_ban_until)
                    VALUES (:uid, 0, 0, NULL)
                    ON DUPLICATE KEY UPDATE shadow_ban_until = NULL
                ")->execute(['uid' => $targetUserId]);
            } else {
                $db->prepare("
                    INSERT INTO moderation_strikes (user_id, warning_count, strike_count, shadow_ban_until)
                    VALUES (:uid, 0, 0, NULL)
                    ON CONFLICT (user_id) DO UPDATE SET shadow_ban_until = NULL
                ")->execute(['uid' => $targetUserId]);
            }
            $auth->logAction($adminId, 'LIFT_SHADOW_BAN', "Shadow ban lifted for {$targetUserId}");
            respond_action(['success' => true, 'message' => 'Shadow ban lifted.']);

        case 'promote_moderator':
            if (!in_array((string)$target['role'], ['student', 'faculty', 'alumni'], true)) {
                respond_action(['success' => false, 'message' => 'Only student, faculty, or alumni accounts can be moderators.'], 409);
            }
            $db->prepare("UPDATE users SET is_moderator = TRUE WHERE user_id = :uid")->execute(['uid' => $targetUserId]);
            $auth->logAction($adminId, 'PROMOTE_MODERATOR', "Promoted user {$targetUserId} as moderator");
            respond_action(['success' => true, 'message' => 'User promoted to moderator.']);

        case 'revoke_moderator':
            $db->prepare("UPDATE users SET is_moderator = FALSE WHERE user_id = :uid")->execute(['uid' => $targetUserId]);
            $auth->logAction($adminId, 'REVOKE_MODERATOR', "Revoked moderator role from user {$targetUserId}");
            respond_action(['success' => true, 'message' => 'Moderator access revoked.']);

        case 'reset_password':
            if ($newPassword === '') {
                respond_action(['success' => false, 'message' => 'new_password is required.'], 400);
            }
            $hash = password_hash($newPassword, PASSWORD_BCRYPT);
            $db->beginTransaction();
            $updateStmt = $db->prepare("UPDATE users SET password_hash = :hash WHERE user_id = :uid");
            $updateStmt->execute(['hash' => $hash, 'uid' => $targetUserId]);
            if ($updateStmt->rowCount() < 1) {
                throw new RuntimeException('Password update affected no rows.');
            }

            $verifyStmt = $db->prepare("SELECT password_hash FROM users WHERE user_id = :uid LIMIT 1");
            $verifyStmt->execute(['uid' => $targetUserId]);
            $storedHash = (string)($verifyStmt->fetchColumn() ?: '');
            if ($storedHash === '' || !password_verify($newPassword, $storedHash)) {
                throw new RuntimeException('Stored password verification failed after update.');
            }

            $db->prepare("DELETE FROM password_resets WHERE email = :email")
               ->execute(['email' => (string)$target['email']]);
            $db->prepare("DELETE FROM sessions WHERE user_id = :uid")
               ->execute(['uid' => $targetUserId]);
            $auth->logAction($adminId, 'RESET_PASSWORD', "Password reset for user {$targetUserId}");
            $db->commit();
            respond_action(['success' => true, 'message' => 'Password reset successfully.']);

        default:
            respond_action(['success' => false, 'message' => 'Unsupported admin action.'], 400);
    }
} catch (Throwable $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    respond_action(['success' => false, 'message' => 'Admin action failed.', 'error' => $e->getMessage()], 500);
}
