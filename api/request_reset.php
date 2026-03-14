<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include_once '../config/Database.php';
include_once __DIR__ . '/_password_reset_schema.php';
include_once '../helpers/EmailService.php';

$database = new Database();
$db = $database->getConnection();
ensure_password_reset_schema($db);

$data = json_decode(file_get_contents("php://input"));
$email = strtolower(trim((string)($data->email ?? '')));

if ($email === '') {
    http_response_code(400);
    echo json_encode(["message" => "Email is required."]);
    exit;
}

$stmt = $db->prepare("
    SELECT u.user_id, COALESCE(NULLIF(TRIM(p.full_name), ''), split_part(u.email, '@', 1)) AS full_name
    FROM users u
    LEFT JOIN profiles p ON p.user_id = u.user_id
    WHERE LOWER(u.email) = :email
    LIMIT 1
");
$stmt->execute(['email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = (new DateTimeImmutable('+30 minutes'))->format('Y-m-d H:i:s');

    $db->prepare("DELETE FROM password_resets WHERE email = :email")->execute(['email' => $email]);

    $ins = $db->prepare("
        INSERT INTO password_resets (email, token, token_hash, expires_at, requested_ip, user_agent)
        VALUES (:email, :token, :token_hash, :expires_at, :requested_ip, :user_agent)
    ");
    $ins->execute([
        'email' => $email,
        'token' => $token,
        'token_hash' => $tokenHash,
        'expires_at' => $expiresAt,
        'requested_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000)
    ]);

    $resetUrl = password_reset_base_url() . '/reset.php?token=' . urlencode($token);
    $safeName = htmlspecialchars((string)($user['full_name'] ?? 'RJITian'), ENT_QUOTES, 'UTF-8');
    $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');

    $emailService = new EmailService();
    $emailService->send(
        $email,
        'Reset your RJIT Alumni Portal password',
        "
        <html>
        <body style='font-family:Arial,sans-serif;color:#111827;line-height:1.6;'>
            <div style='max-width:640px;margin:0 auto;padding:24px;'>
                <h2 style='margin:0 0 12px;'>Reset your password</h2>
                <p>Hello {$safeName},</p>
                <p>We received a request to reset the password for your RJIT Alumni Portal account.</p>
                <p>This link will expire in <strong>30 minutes</strong>.</p>
                <p style='margin:24px 0;'>
                    <a href='{$safeUrl}' style='background:#2563eb;color:#fff;text-decoration:none;padding:12px 18px;border-radius:10px;display:inline-block;font-weight:600;'>
                        Reset Password
                    </a>
                </p>
                <p>If the button does not work, use this link:</p>
                <p><a href='{$safeUrl}'>{$safeUrl}</a></p>
                <p>If you did not request this, you can ignore this email.</p>
                <p style='margin-top:24px;'>RJIT Alumni Portal</p>
            </div>
        </body>
        </html>
        ",
        true
    );
}

echo json_encode(["message" => "If the email exists, a reset link has been sent."]);
