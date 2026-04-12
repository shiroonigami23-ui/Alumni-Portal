<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

if ($argc < 3) {
    fwrite(STDERR, "Usage: php scripts/reset_admin_password.php <admin_email> <new_password>\n");
    exit(1);
}

$email = trim((string)$argv[1]);
$newPassword = (string)$argv[2];

if ($email === '' || strlen($newPassword) < 8) {
    fwrite(STDERR, "Provide a valid email and password (min 8 chars).\n");
    exit(1);
}

$db = (new Database())->getConnection();
if (!$db) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

$check = $db->prepare("SELECT user_id FROM users WHERE email = :email AND role = 'admin' LIMIT 1");
$check->execute([':email' => $email]);
$userId = (int)($check->fetchColumn() ?: 0);
if ($userId <= 0) {
    fwrite(STDERR, "Admin user not found for email: {$email}\n");
    exit(1);
}

$hash = password_hash($newPassword, PASSWORD_DEFAULT);
$update = $db->prepare("UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE user_id = :uid");
$update->execute([
    ':hash' => $hash,
    ':uid' => $userId,
]);

fwrite(STDOUT, "Password reset done for admin user_id {$userId} ({$email}).\n");
