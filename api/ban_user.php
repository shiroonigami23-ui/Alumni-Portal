<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$admin_id = $auth->validateRequest();

// 1. Check if actor is Admin (Blueprint Section 4.D)
$u_stmt = $db->prepare("SELECT role FROM users WHERE user_id = :uid");
$u_stmt->execute(['uid' => $admin_id]);
$user = $u_stmt->fetch(PDO::FETCH_ASSOC);

if ($user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["message" => "Forbidden. Only Admin can permanently ban users."]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->target_id)) {
    try {
        $db->beginTransaction();

        $targetStmt = $db->prepare("SELECT user_id, role FROM users WHERE user_id = :tid FOR UPDATE");
        $targetStmt->execute(['tid' => $data->target_id]);
        $target = $targetStmt->fetch(PDO::FETCH_ASSOC);
        if (!$target) {
            throw new Exception("Target user not found.");
        }
        if ((int)$target['user_id'] === (int)$admin_id) {
            throw new Exception("Admin cannot ban self.");
        }

        // 2. Set status to 'banned'
        $query = "UPDATE users SET status = 'banned', suspension_expires_at = NULL WHERE user_id = :tid";
        $db->prepare($query)->execute(['tid' => $data->target_id]);

        // 3. Device/IP blacklisting for permanent ban
        $reason = trim((string)($data->reason ?? 'Permanent ban by admin'));
        $seen = [];
        $src = $db->prepare("
            SELECT device_fingerprint, ip_address::text AS ip_address, user_agent
            FROM sessions
            WHERE user_id = :uid
            UNION
            SELECT device_fingerprint, ip_address::text AS ip_address, user_agent
            FROM activity_logs
            WHERE user_id = :uid
        ");
        $src->execute(['uid' => $data->target_id]);
        $insertBan = $db->prepare("
            INSERT INTO device_bans (device_fingerprint, ip_address, banned_by_admin_id, reason)
            VALUES (:fp, CAST(:ip AS inet), :aid, :reason)
            ON CONFLICT (device_fingerprint, ip_address) DO NOTHING
        ");
        while ($row = $src->fetch(PDO::FETCH_ASSOC)) {
            $ip = trim((string)($row['ip_address'] ?? ''));
            if ($ip === '') continue;
            $fp = trim((string)($row['device_fingerprint'] ?? ''));
            if ($fp === '') {
                $ua = (string)($row['user_agent'] ?? '');
                $fp = substr(hash('sha256', $ua . '|' . $ip), 0, 120);
            }
            $key = $fp . '|' . $ip;
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $insertBan->execute([
                'fp' => $fp,
                'ip' => $ip,
                'aid' => $admin_id,
                'reason' => $reason
            ]);
        }

        // 4. Remove active sessions for banned user.
        $db->prepare("DELETE FROM sessions WHERE user_id = :uid")->execute(['uid' => $data->target_id]);
        
        $auth->logAction($admin_id, "PERMANENT_BAN", "Admin banned user " . $data->target_id);
        
        $db->commit();
        echo json_encode(["message" => "User permanently banned and device blacklist applied where available."]);
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(["message" => "Ban failed: " . $e->getMessage()]);
    }
}
?>
