<?php
/**
 * Apply for Job - Submit job application with resume
 * Fixed: Proper multipart/form-data handling for file uploads
 * Fixed: $_FILES array access
 */

require_once __DIR__ . '/_kernel.php';
$api = api_endpoint(['access' => 'member']);   // who may call this — enforced before anything below
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../config/Database.php';

header('Content-Type: application/json');

// Authenticate user
$database = new Database();
$pdo = $database->getConnection();

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['message' => 'Database connection failed']);
    exit;
}

$auth = new Auth($pdo);
$user_id = $auth->validateRequest();

$user_stmt = $pdo->prepare("SELECT user_id, role FROM users WHERE user_id = :uid");
$user_stmt->execute(['uid' => $user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(401);
    echo json_encode(['message' => 'Unauthorized']);
    exit;
}

// Only alumni and students can apply for jobs
if (!in_array($user['role'], ['alumni', 'student'])) {
    http_response_code(403);
    echo json_encode(['message' => 'Only alumni and students can apply for jobs']);
    exit;
}

try {
    // Get job_id from POST data
    $job_id = $_POST['job_id'] ?? null;
    $cover_letter = $_POST['cover_letter'] ?? '';
    
    // Validate inputs
    if (!$job_id) {
        http_response_code(400);
        echo json_encode(['message' => "Incomplete data. 'job_id' required."]);
        exit;
    }
    
    // Check if resume file was uploaded
    if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode([
            'message' => "Resume file required.",
            'debug' => [
                'files_exist' => isset($_FILES['resume']),
                'error_code' => $_FILES['resume']['error'] ?? 'NO_FILE',
                'error_message' => $_FILES['resume']['error'] ?? null
            ]
        ]);
        exit;
    }
    
    // Check if job exists
    $stmt = $pdo->prepare("SELECT job_id, company_name, poster_id, COALESCE(status, 'open') AS status FROM jobs WHERE job_id = :job_id");
    $stmt->execute([':job_id' => $job_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job) {
        http_response_code(404);
        echo json_encode(['message' => 'Job not found']);
        exit;
    }
    if ($job['status'] !== 'open') {
        http_response_code(409);
        echo json_encode(['message' => 'This job is closed and no longer accepts applications.']);
        exit;
    }
    if ((int)$job['poster_id'] === (int)$user['user_id']) {
        http_response_code(400);
        echo json_encode(['message' => "You can't apply to a job you posted."]);
        exit;
    }
    
    // Check if user already applied
    $stmt = $pdo->prepare("SELECT application_id FROM job_applications WHERE job_id = :job_id AND applicant_id = :user_id");
    $stmt->execute([
        ':job_id' => $job_id,
        ':user_id' => $user['user_id']
    ]);
    
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['message' => 'You have already applied for this job']);
        exit;
    }
    
    // Handle resume file upload
    $file = $_FILES['resume'];
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Allowed resume formats
    $allowed_extensions = ['pdf', 'doc', 'docx'];
    
    if (!in_array($file_ext, $allowed_extensions)) {
        http_response_code(400);
        echo json_encode([
            'message' => 'Invalid file format. Only PDF, DOC, DOCX allowed.',
            'uploaded_extension' => $file_ext
        ]);
        exit;
    }
    
    // File size limit: 5 MB
    $max_size = 5 * 1024 * 1024; // 5 MB in bytes
    if ($file_size > $max_size) {
        http_response_code(400);
        echo json_encode([
            'message' => 'File too large. Maximum size is 5 MB.',
            'uploaded_size' => round($file_size / 1024 / 1024, 2) . ' MB'
        ]);
        exit;
    }
    
    // Store the résumé in the database asset store: survives app-server changes and
    // is only reachable through an unguessable token (never a public storage/ path).
    require_once __DIR__ . '/_asset_store.php';
    $asset = store_uploaded_asset($pdo, (int)$user['user_id'], $file, 'file', 'resume');
    if (!$asset || empty($asset['url'])) {
        http_response_code(500);
        echo json_encode(['message' => 'Could not save your résumé. Please try again.']);
        exit;
    }
    $relative_path = $asset['url'];

    $stmt = $pdo->prepare("
        INSERT INTO job_applications 
        (job_id, applicant_id, resume_path, cover_letter, status)
        VALUES 
        (:job_id, :user_id, :resume_path, :cover_letter, 'pending')
        RETURNING application_id
    ");
    
    $stmt->execute([
        ':job_id' => $job_id,
        ':user_id' => $user['user_id'],
        ':resume_path' => $relative_path,
        ':cover_letter' => $cover_letter
    ]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $application_id = $result['application_id'];
    
    // Create notification for job poster
    $job_poster_query = $pdo->prepare("SELECT poster_id FROM jobs WHERE job_id = :job_id");
    $job_poster_query->execute([':job_id' => $job_id]);
    $poster = $job_poster_query->fetch(PDO::FETCH_ASSOC);
    
    if ($poster) {
        $notif_stmt = $pdo->prepare("
            INSERT INTO notifications 
            (user_id, notification_type, content, related_user_id, created_at)
            VALUES 
            (:user_id, 'new_applicant'::notification_type, :content, :applicant_id, CURRENT_TIMESTAMP)
        ");
        
        $notif_stmt->execute([
            ':user_id' => $poster['poster_id'],
            ':content' => 'New application received for your job posting',
            ':applicant_id' => $user['user_id']
        ]);
    }
    
    // Log activity
    $log_file = __DIR__ . '/../storage/logs/activity.log';
    if (!is_dir(dirname($log_file))) { @mkdir(dirname($log_file), 0750, true); }
    $log_message = sprintf(
        "[%s] User %d applied for job %d (Application ID: %d)\n",
        date('Y-m-d H:i:s'),
        $user['user_id'],
        $job_id,
        $application_id
    );
    file_put_contents($log_file, $log_message, FILE_APPEND);
    
    echo json_encode([
        'message' => 'Application submitted successfully',
        'application_id' => $application_id,
        'job_id' => $job_id,
        'resume_path' => $relative_path
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'message' => api_error_message($e)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'message' => api_error_message($e)
    ]);
}
