<?php
/**
 * Upload a proof-of-alumni document (PDF) for an account that is still awaiting verification.
 * POST multipart: document=<file.pdf>
 *
 * Only accounts that aren't active yet may use this — an active account uploading here is refused instead of
 * being knocked back to "pending" (the old behaviour locked people out). Files go to the database asset store
 * (private, admin-reviewed), never to a world-writable folder.
 */
require_once __DIR__ . '/_kernel.php';
$api = api_endpoint(['access' => 'member']);   // who may call this — enforced before anything below
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/Database.php';
require_once '../middleware/Auth.php';
require_once __DIR__ . '/_asset_store.php';

$db = (new Database())->getConnection();
$auth = new Auth($db);
$uid = (int)$auth->validateRequest();

$fail = function (int $code, string $msg) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
};

$st = $db->prepare("SELECT status FROM users WHERE user_id = :u");
$st->execute([':u' => $uid]);
$status = (string)$st->fetchColumn();
if (!in_array($status, ['pending', 'pending_verification', 'unverified'], true)) {
    $fail(409, 'Your account is already verified — no document needed.');
}

$file = $_FILES['document'] ?? null;
if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    $fail(400, 'Attach your document (PDF) in the "document" field.');
}
if (strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION)) !== 'pdf') {
    $fail(415, 'Verification documents must be PDF files.');
}
if ((int)($file['size'] ?? 0) > 10 * 1024 * 1024) {
    $fail(413, 'That PDF is too large. Please keep it under 10 MB.');
}

try {
    $asset = store_uploaded_asset($db, $uid, $file, 'document', 'verification');
    if (!$asset) {
        $fail(400, 'The upload failed. Please try again.');
    }
    $db->prepare("UPDATE users SET status = 'pending_verification' WHERE user_id = :u AND status IN ('pending', 'unverified')")
       ->execute([':u' => $uid]);
    try {
        $auth->logAction($uid, 'VERIFICATION_UPLOAD', 'Uploaded a verification document');
    } catch (Throwable $e) {
    }
    echo json_encode(['success' => true, 'message' => 'Document uploaded. An admin will review it shortly.']);
} catch (Throwable $e) {
    error_log('upload_verification: ' . $e->getMessage());
    $fail(500, 'Could not save your document. Please try again.');
}
