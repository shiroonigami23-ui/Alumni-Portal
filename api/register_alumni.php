<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';
include_once '../helpers/StudentLifecycleHelper.php';

$database = new Database();
$db = $database->getConnection();
$authGuard = new Auth($db);
$data = json_decode(file_get_contents("php://input"));

if ($authGuard->isCurrentDeviceBanned()) {
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "message" => "This device is banned from registration."
    ]);
    exit();
}

if (!$data || empty($data->email)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Email is required for alumni registration."
    ]);
    exit();
}

$email = strtolower(trim((string)$data->email));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Please provide a valid email address."
    ]);
    exit();
}

if (str_ends_with($email, '@rjit.ac.in') && !StudentLifecycleHelper::isEligibleForAlumniRoleByEmail($email)) {
    $gradYear = StudentLifecycleHelper::expectedGraduationYearForEmail($email);
    $cutoff = $gradYear ? StudentLifecycleHelper::graduationCutoffDate($gradYear)->format('Y-m-d') : 'July 1 of graduation year';
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "This college email appears to be for an active student. Alumni role from this ID is allowed only after {$cutoff}."
    ]);
    exit();
}

try {
    $checkStmt = $db->prepare("SELECT user_id FROM users WHERE email = :email");
    $checkStmt->execute(['email' => $email]);
    if ($checkStmt->rowCount() > 0) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Email already registered."
        ]);
        exit();
    }

    $name = trim((string)($data->name ?? ''));
    if ($name === '') {
        $name = 'Alumni Member';
    }

    $joiningYear = null;
    if (isset($data->joining_year) && $data->joining_year !== '') {
        $candidateYear = (int)$data->joining_year;
        $currentYear = (int)date('Y');
        if ($candidateYear >= 1999 && $candidateYear <= $currentYear) {
            $joiningYear = $candidateYear;
        }
    }

    $branch = trim((string)($data->branch ?? ''));
    $company = trim((string)($data->current_company ?? ''));
    $position = trim((string)($data->position ?? ''));
    $bio = trim((string)($data->bio ?? ''));
    $mobile = trim((string)($data->mobile_number ?? ''));
    $helpAlumniMates = trim((string)($data->help_alumni_mates ?? ''));

    // Password is optional for alumni signup UI; generate one when omitted.
    $plainPassword = trim((string)($data->password ?? ''));
    if ($plainPassword === '') {
        $plainPassword = bin2hex(random_bytes(12));
    }
    $hash = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]);

    $db->beginTransaction();

    $uStmt = $db->prepare("
        INSERT INTO users (email, password_hash, role, status)
        VALUES (:email, :password_hash, 'alumni', 'pending')
        RETURNING user_id
    ");
    $uStmt->execute([
        'email' => $email,
        'password_hash' => $hash
    ]);
    $userId = $uStmt->fetchColumn();

    $pStmt = $db->prepare("
        INSERT INTO profiles (
            user_id,
            full_name,
            bio,
            joining_year,
            branch,
            current_company,
            job_role,
            contact_number,
            help_alumni_mates
        ) VALUES (
            :user_id,
            :full_name,
            :bio,
            :joining_year,
            :branch,
            :current_company,
            :job_role,
            :contact_number,
            :help_alumni_mates
        )
    ");
    $pStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $pStmt->bindValue(':full_name', $name, PDO::PARAM_STR);
    $pStmt->bindValue(':bio', $bio !== '' ? $bio : null, $bio !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $pStmt->bindValue(':joining_year', $joiningYear, $joiningYear !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $pStmt->bindValue(':branch', $branch !== '' ? $branch : null, $branch !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $pStmt->bindValue(':current_company', $company !== '' ? $company : null, $company !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $pStmt->bindValue(':job_role', $position !== '' ? $position : null, $position !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $pStmt->bindValue(':contact_number', $mobile !== '' ? $mobile : null, $mobile !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $pStmt->bindValue(':help_alumni_mates', $helpAlumniMates !== '' ? $helpAlumniMates : null, $helpAlumniMates !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $pStmt->execute();

    $db->commit();

    http_response_code(201);
    echo json_encode([
        "success" => true,
        "message" => "Alumni registration submitted successfully. Your account is pending approval."
    ]);
} catch (Throwable $e) {
    if ($db && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Registration failed: " . $e->getMessage()
    ]);
}
?>
