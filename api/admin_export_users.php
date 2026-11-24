<?php
/**
 * Admin: download members as CSV (opens in Excel / Google Sheets).
 * GET ?role=alumni|student|faculty|admin&status=active  (optional filters)
 * Values are neutralised against spreadsheet formula injection.
 */
include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$db = (new Database())->getConnection();
$auth = new Auth($db);
$adminId = (int)$auth->validateRequest();

$r = $db->prepare("SELECT role FROM users WHERE user_id = :u");
$r->execute(['u' => $adminId]);
if ((string)$r->fetchColumn() !== 'admin') {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Admins only.']);
    exit;
}

$where = ['1=1'];
$params = [];
if (in_array($_GET['role'] ?? '', ['alumni', 'student', 'faculty', 'admin'], true)) {
    $where[] = 'u.role = :role';
    $params['role'] = $_GET['role'];
}
if (preg_match('/^[a-z_]{3,30}$/', (string)($_GET['status'] ?? ''))) {
    $where[] = 'u.status = :status';
    $params['status'] = $_GET['status'];
}

$stmt = $db->prepare("
    SELECT u.user_id, u.email, u.role, u.status, p.full_name, p.branch, p.graduation_year,
           p.current_company, p.job_role, p.location_city, p.location_country, p.linkedin_url,
           u.created_at, u.last_login
    FROM users u LEFT JOIN profiles p ON p.user_id = u.user_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY u.user_id ASC
");
$stmt->execute($params);

$auth->logAction($adminId, 'EXPORT_USERS_CSV', json_encode($params));

$cell = function ($v) {
    $v = (string)$v;
    return ($v !== '' && strpbrk($v[0], "=+-@\t\r") !== false) ? "'" . $v : $v;
};

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="rjit-members-' . date('Y-m-d') . '.csv"');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows names correctly
fputcsv($out, ['User ID', 'Email', 'Role', 'Status', 'Name', 'Branch', 'Batch', 'Company', 'Job role', 'City', 'Country', 'LinkedIn', 'Joined', 'Last login']);
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    fputcsv($out, array_map($cell, $row));
}
fclose($out);
