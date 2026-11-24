<?php
/**
 * Shared learning resources (notes, sheets, links, videos).
 * GET  ?action=list[&category=&q=]
 * POST ?action=create {title, description?, category, file_url (https), resource_type}
 * POST ?action=download {resource_id}   → counts a download, returns the URL
 * POST ?action=delete {resource_id}     owner or admin
 */
require_once __DIR__ . '/_kernel.php';
$api = api_endpoint(['access' => 'member']);   // who may call this — enforced before anything below
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/Database.php';
include_once '../middleware/Auth.php';

const RES_CATEGORIES = ['placements', 'notes', 'higher-studies', 'projects', 'internships', 'interview-prep', 'career', 'other'];
const RES_TYPES = ['link', 'document', 'video', 'sheet', 'repo'];

$db = (new Database())->getConnection();
$auth = new Auth($db);
$userId = (int)$auth->validateRequest();
$r = $db->prepare("SELECT role FROM users WHERE user_id = :u");
$r->execute(['u' => $userId]);
$isAdmin = $r->fetchColumn() === 'admin';
$data = json_decode((string)file_get_contents('php://input'), true) ?: [];
$out = function (array $p, int $code = 200) {
    http_response_code($code);
    echo json_encode($p);
    exit;
};

try {
    switch ($_GET['action'] ?? 'list') {
        case 'list':
            $where = ["COALESCE(r.status, 'approved') = 'approved'"];
            $params = [];
            if (in_array($_GET['category'] ?? '', RES_CATEGORIES, true)) {
                $where[] = 'r.category = :c';
                $params['c'] = $_GET['category'];
            }
            $q = trim(mb_substr((string)($_GET['q'] ?? ''), 0, 60));
            if ($q !== '') {
                $where[] = '(r.title ILIKE :q1 OR r.description ILIKE :q2)';
                $params['q1'] = $params['q2'] = '%' . $q . '%';
            }
            $s = $db->prepare("SELECT r.resource_id, r.title, r.description, r.category, r.file_url, r.resource_type, r.download_count, r.created_at,
                                      r.uploaded_by, COALESCE(p.full_name, 'RJIT member') AS uploader_name
                               FROM resources r LEFT JOIN profiles p ON p.user_id = r.uploaded_by
                               WHERE " . implode(' AND ', $where) . " ORDER BY r.download_count DESC, r.created_at DESC LIMIT 100");
            $s->execute($params);
            $rows = $s->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$row) {
                $row['can_delete'] = $isAdmin || (int)$row['uploaded_by'] === $userId;
            }
            $out(['success' => true, 'data' => $rows, 'categories' => RES_CATEGORIES]);

        case 'create':
            $title = trim(mb_substr(strip_tags((string)($data['title'] ?? '')), 0, 160));
            $url = trim((string)($data['file_url'] ?? ''));
            $cat = (string)($data['category'] ?? 'other');
            if (mb_strlen($title) < 3) {
                $out(['success' => false, 'message' => 'Give the resource a title.'], 400);
            }
            if (!preg_match('#^https://[^\s<>"]{4,}$#i', $url)) {
                $out(['success' => false, 'message' => 'Add a link that starts with https://'], 400);
            }
            $s = $db->prepare("INSERT INTO resources (uploaded_by, title, description, category, file_url, resource_type, status)
                               VALUES (:u, :t, :d, :c, :f, :ty, 'approved') RETURNING resource_id");
            $s->execute(['u' => $userId, 't' => $title, 'd' => trim(mb_substr(strip_tags((string)($data['description'] ?? '')), 0, 600)) ?: null,
                'c' => in_array($cat, RES_CATEGORIES, true) ? $cat : 'other', 'f' => $url,
                'ty' => in_array($data['resource_type'] ?? '', RES_TYPES, true) ? $data['resource_type'] : 'link']);
            $out(['success' => true, 'message' => 'Resource shared. Thank you!', 'resource_id' => (int)$s->fetchColumn()], 201);

        case 'download':
            $s = $db->prepare("UPDATE resources SET download_count = download_count + 1 WHERE resource_id = :id RETURNING file_url");
            $s->execute(['id' => (int)($data['resource_id'] ?? 0)]);
            $url = $s->fetchColumn();
            $url ? $out(['success' => true, 'url' => $url]) : $out(['success' => false, 'message' => 'Resource not found.'], 404);

        case 'delete':
            $id = (int)($data['resource_id'] ?? 0);
            $s = $db->prepare("DELETE FROM resources WHERE resource_id = :id" . ($isAdmin ? '' : ' AND uploaded_by = :u'));
            $s->execute($isAdmin ? ['id' => $id] : ['id' => $id, 'u' => $userId]);
            $s->rowCount() ? $out(['success' => true, 'message' => 'Resource removed.']) : $out(['success' => false, 'message' => 'You can only remove resources you shared.'], 403);

        default:
            $out(['success' => false, 'message' => 'Unknown action.'], 404);
    }
} catch (Throwable $e) {
    error_log('resources: ' . $e->getMessage());
    $out(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}
