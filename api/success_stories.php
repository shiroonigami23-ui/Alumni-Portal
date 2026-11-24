<?php
/**
 * Alumni success stories (one per alumnus; admins approve before they're public).
 * GET  ?action=list[&mine=1|&pending=1]      approved stories (mine / review queue for admins)
 * POST ?action=create   {title, content, featured_image?}   create or update my story (back to review)
 * POST ?action=approve  {story_id}   admin
 * POST ?action=reject   {story_id}   admin (deletes)
 * POST ?action=view     {story_id}   count a read
 */
require_once __DIR__ . '/_kernel.php';
$api = api_endpoint(['access' => 'member', 'actions' => ['approve' => 'admin', 'reject' => 'admin']]);   // who may call this — enforced before anything below
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once __DIR__ . '/_content_store.php';

$db = (new Database())->getConnection();
$auth = new Auth($db);
$userId = (int)$auth->validateRequest();
$r = $db->prepare("SELECT role FROM users WHERE user_id = :u");
$r->execute(['u' => $userId]);
$role = (string)$r->fetchColumn();
$isAdmin = $role === 'admin';
$data = json_decode((string)file_get_contents('php://input'), true) ?: [];
$out = function (array $p, int $code = 200) {
    http_response_code($code);
    echo json_encode($p);
    exit;
};

try {
    switch ($_GET['action'] ?? 'list') {
        case 'list':
            $where = 's.is_featured = TRUE';
            $params = [];
            if (!empty($_GET['mine'])) {
                $where = 's.alumni_user_id = :u';
                $params['u'] = $userId;
            } elseif (!empty($_GET['pending']) && $isAdmin) {
                $where = 's.is_featured = FALSE';
            }
            $s = $db->prepare("SELECT s.story_id, s.alumni_user_id AS user_id, s.title, s.story_content_file_path, s.featured_image_url,
                                      s.is_featured, s.view_count, s.created_at, s.updated_at,
                                      COALESCE(p.full_name, 'RJIT alumnus') AS full_name, p.current_company, p.job_role, p.graduation_year, u.role
                               FROM success_stories s JOIN users u ON u.user_id = s.alumni_user_id LEFT JOIN profiles p ON p.user_id = s.alumni_user_id
                               WHERE $where ORDER BY s.display_order DESC, s.updated_at DESC LIMIT 60");
            $s->execute($params);
            $rows = [];
            foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $path = (string)$row['story_content_file_path'];
                if (extract_content_token($path)) {
                    $content = (string)(load_content_payload($db, $path)['content'] ?? '');
                } else {
                    $file = realpath(dirname(__DIR__) . '/' . $path);
                    $base = realpath(dirname(__DIR__) . '/storage');
                    $content = ($file && $base && strpos($file, $base) === 0 && is_file($file)) ? (string)file_get_contents($file) : '';
                }
                unset($row['story_content_file_path']);
                $row['content'] = $content;
                $row['is_featured'] = filter_var($row['is_featured'], FILTER_VALIDATE_BOOLEAN);
                $row['is_mine'] = (int)$row['user_id'] === $userId;
                $rows[] = $row;
            }
            $out(['success' => true, 'data' => $rows]);

        case 'create':
            if (!in_array($role, ['alumni', 'faculty', 'admin'], true)) {
                $out(['success' => false, 'message' => 'Success stories are shared by alumni and faculty.'], 403);
            }
            $title = trim(mb_substr(strip_tags((string)($data['title'] ?? '')), 0, 120));
            $content = trim(mb_substr(strip_tags((string)($data['content'] ?? '')), 0, 6000));
            $image = trim((string)($data['featured_image'] ?? ''));
            if (mb_strlen($title) < 5 || mb_strlen($content) < 50) {
                $out(['success' => false, 'message' => 'Add a title and at least a few sentences (50+ characters) about your journey.'], 400);
            }
            if ($image !== '' && !preg_match('#^(https://[^\s<>"]+|api/asset\.php\?token=[a-f0-9]+)$#i', $image)) {
                $out(['success' => false, 'message' => 'Cover image must be an https:// link.'], 400);
            }
            $old = $db->prepare("SELECT story_content_file_path FROM success_stories WHERE alumni_user_id = :u");
            $old->execute(['u' => $userId]);
            $oldPath = $old->fetchColumn();
            $pointer = $oldPath && extract_content_token((string)$oldPath)
                ? update_content_payload($db, (string)$oldPath, ['content' => $content, 'attachments' => []], $userId, 'story')
                : store_content_payload($db, $userId, ['content' => $content, 'attachments' => []], 'story');
            $s = $db->prepare("INSERT INTO success_stories (alumni_user_id, title, story_content_file_path, featured_image_url, is_featured)
                               VALUES (:u, :t, :p, :i, :f)
                               ON CONFLICT (alumni_user_id) DO UPDATE SET title = EXCLUDED.title, story_content_file_path = EXCLUDED.story_content_file_path,
                               featured_image_url = EXCLUDED.featured_image_url, is_featured = EXCLUDED.is_featured, updated_at = CURRENT_TIMESTAMP
                               RETURNING story_id");
            $s->execute(['u' => $userId, 't' => $title, 'p' => $pointer, 'i' => $image ?: null, 'f' => $isAdmin ? 'true' : 'false']);
            $out(['success' => true, 'story_id' => (int)$s->fetchColumn(),
                'message' => $isAdmin ? 'Story published.' : 'Thanks for sharing! Your story will appear once an admin approves it.'], 201);

        case 'approve':
        case 'reject':
            if (!$isAdmin) {
                $out(['success' => false, 'message' => 'Admins only.'], 403);
            }
            $id = (int)($data['story_id'] ?? 0);
            if ($_GET['action'] === 'approve') {
                $s = $db->prepare("UPDATE success_stories SET is_featured = TRUE, featured_by_admin_id = :a, updated_at = CURRENT_TIMESTAMP WHERE story_id = :id");
                $s->execute(['a' => $userId, 'id' => $id]);
            } else {
                $s = $db->prepare("DELETE FROM success_stories WHERE story_id = :id");
                $s->execute(['id' => $id]);
            }
            if (!$s->rowCount()) {
                $out(['success' => false, 'message' => 'Story not found.'], 404);
            }
            $auth->logAction($userId, 'STORY_' . strtoupper($_GET['action']), "Story #$id");
            $out(['success' => true, 'message' => $_GET['action'] === 'approve' ? 'Story approved and published.' : 'Story removed.']);

        case 'view':
            $db->prepare("UPDATE success_stories SET view_count = view_count + 1 WHERE story_id = :id AND is_featured = TRUE")
               ->execute(['id' => (int)($data['story_id'] ?? 0)]);
            $out(['success' => true]);

        default:
            $out(['success' => false, 'message' => 'Unknown action.'], 404);
    }
} catch (Throwable $e) {
    error_log('success_stories: ' . $e->getMessage());
    $out(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}
