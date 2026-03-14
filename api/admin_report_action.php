<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once __DIR__ . '/_moderation_schema.php';

function respond_report_action(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

try {
    $adminId = (int)$auth->validateRequest();
    if (moderation_get_user_role($db, $adminId) !== 'admin') {
        respond_report_action(['success' => false, 'message' => 'Admin access only.'], 403);
    }

    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $reportId = (int)($data['report_id'] ?? 0);
    $action = trim((string)($data['action'] ?? ''));

    if ($reportId <= 0 || $action === '') {
        respond_report_action(['success' => false, 'message' => 'report_id and action are required.'], 400);
    }

    if ($action === 'dismiss') {
        $db->prepare("UPDATE reports SET status = 'rejected'::report_status WHERE report_id = :rid")
           ->execute(['rid' => $reportId]);
        $auth->logAction($adminId, 'DISMISS_REPORT', "Dismissed report {$reportId}");
        respond_report_action(['success' => true, 'message' => 'Report dismissed.']);
    }

    if ($action === 'resolve') {
        $db->prepare("UPDATE reports SET status = 'resolved'::report_status WHERE report_id = :rid")
           ->execute(['rid' => $reportId]);
        $auth->logAction($adminId, 'RESOLVE_REPORT', "Resolved report {$reportId}");
        respond_report_action(['success' => true, 'message' => 'Report resolved.']);
    }

    if ($action === 'resolve_post_reports') {
        $postId = (int)($data['post_id'] ?? 0);
        if ($postId <= 0) {
            respond_report_action(['success' => false, 'message' => 'post_id is required.'], 400);
        }
        $db->prepare("UPDATE reports SET status = 'resolved'::report_status WHERE reported_post_id = :pid")
           ->execute(['pid' => $postId]);
        $auth->logAction($adminId, 'RESOLVE_POST_REPORTS', "Resolved reports for post {$postId}");
        respond_report_action(['success' => true, 'message' => 'Reports for this post marked resolved.']);
    }

    respond_report_action(['success' => false, 'message' => 'Unsupported report action.'], 400);
} catch (Throwable $e) {
    respond_report_action(['success' => false, 'message' => 'Report action failed.', 'error' => $e->getMessage()], 500);
}
