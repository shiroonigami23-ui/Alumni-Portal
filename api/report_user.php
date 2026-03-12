<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

try {
    $reporter_id = (int)$auth->validateRequest();
    $data = json_decode(file_get_contents("php://input"), true) ?: [];
    $target_id = isset($data['target_user_id']) ? (int)$data['target_user_id'] : 0;
    $reason = strtolower(trim((string)($data['reason'] ?? 'harassment')));
    $custom_reason = trim((string)($data['custom_reason'] ?? ''));

    if ($target_id <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "target_user_id is required."]);
        exit;
    }
    if ($target_id === $reporter_id) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "You cannot report yourself."]);
        exit;
    }

    $validReasons = ['spam', 'inappropriate', 'harassment', 'misinformation', 'hate_speech', 'violence', 'other'];
    if (!in_array($reason, $validReasons, true)) {
        $reason = 'other';
    }

    $stmt = $db->prepare("SELECT user_id, role FROM users WHERE user_id IN (:rid, :tid)");
    $stmt->execute(['rid' => $reporter_id, 'tid' => $target_id]);
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $reporterRole = '';
    $targetExists = false;
    foreach ($roles as $r) {
        if ((int)$r['user_id'] === $reporter_id) $reporterRole = (string)$r['role'];
        if ((int)$r['user_id'] === $target_id) $targetExists = true;
    }

    if (!$targetExists) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Target user not found."]);
        exit;
    }
    if (!in_array($reporterRole, ['student', 'alumni'], true)) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Only students and alumni can report user harassment/spam."]);
        exit;
    }

    $db->beginTransaction();

    $db->exec("
        CREATE UNIQUE INDEX IF NOT EXISTS idx_reports_unique_user_report
        ON reports (reported_user_id, reporter_user_id)
        WHERE reported_user_id IS NOT NULL
    ");

    $dup = $db->prepare("
        SELECT 1
        FROM reports
        WHERE reported_user_id = :tid
          AND reporter_user_id = :rid
        LIMIT 1
    ");
    $dup->execute(['tid' => $target_id, 'rid' => $reporter_id]);
    if ($dup->fetchColumn()) {
        $db->rollBack();
        echo json_encode(["success" => true, "message" => "You already reported this user."]);
        exit;
    }

    $ins = $db->prepare("
        INSERT INTO reports (reported_user_id, reporter_user_id, reason, custom_reason, status)
        VALUES (:tid, :rid, CAST(:reason AS report_reason), :custom_reason, 'pending'::report_status)
        ON CONFLICT DO NOTHING
    ");
    $ins->execute([
        'tid' => $target_id,
        'rid' => $reporter_id,
        'reason' => $reason,
        'custom_reason' => ($custom_reason !== '' ? $custom_reason : null)
    ]);
    if ($ins->rowCount() === 0) {
        $db->rollBack();
        echo json_encode(["success" => true, "message" => "You already reported this user."]);
        exit;
    }

    $cnt = $db->prepare("
        SELECT COUNT(DISTINCT reporter_user_id)
        FROM reports
        WHERE reported_user_id = :tid
          AND status = 'pending'
    ");
    $cnt->execute(['tid' => $target_id]);
    $reportCount = (int)$cnt->fetchColumn();

    $shadowApplied = false;
    if ($reportCount >= 5) {
        $until = date('Y-m-d H:i:s', strtotime('+3650 days'));
        $db->prepare("
            INSERT INTO moderation_strikes (user_id, warning_count, strike_count, shadow_ban_until)
            VALUES (:uid, 5, 1, :until)
            ON CONFLICT (user_id) DO UPDATE
            SET warning_count = GREATEST(moderation_strikes.warning_count, 5),
                strike_count = GREATEST(moderation_strikes.strike_count, 1),
                shadow_ban_until = CASE
                    WHEN moderation_strikes.shadow_ban_until IS NULL OR moderation_strikes.shadow_ban_until < :until
                    THEN :until
                    ELSE moderation_strikes.shadow_ban_until
                END
        ")->execute(['uid' => $target_id, 'until' => $until]);
        $shadowApplied = true;
    }

    $auth->logAction($reporter_id, "REPORT_USER", "Reported user {$target_id} for {$reason}");
    $db->commit();

    echo json_encode([
        "success" => true,
        "message" => $shadowApplied
            ? "Report submitted. Threshold reached. User is now shadow banned pending admin review."
            : "Report submitted successfully.",
        "data" => [
            "report_count" => $reportCount,
            "shadow_banned" => $shadowApplied
        ]
    ]);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Failed to report user.", "detail" => $e->getMessage()]);
}
?>
