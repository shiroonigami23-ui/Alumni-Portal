<?php
/**
 * Brute-force / rate-limit checks. Run against a server started WITH rate limiting
 * (do not set RATE_LIMIT_DISABLED), e.g.:
 *   LOGIN_MAX_FAILURES=3 php -S 127.0.0.1:8098 router.php &
 *   php tests/integration/security_limits.php http://127.0.0.1:8098
 */
$base = rtrim($argv[1] ?? 'http://127.0.0.1:8098', '/');
$max = (int)(getenv('LOGIN_MAX_FAILURES') ?: 3);
$email = 'lockout.' . bin2hex(random_bytes(3)) . '@example.com';
$post = function (array $body) use ($base) {
    $ch = curl_init("$base/api/login.php");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_POSTFIELDS => json_encode($body)]);
    $out = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, json_decode((string)$out, true)];
};
$fails = 0;
for ($i = 1; $i <= $max; $i++) {
    [$code] = $post(['email' => $email, 'password' => 'wrong-' . $i]);
    $fails += $code === 401 ? 1 : 0;
}
[$code, $json] = $post(['email' => $email, 'password' => 'wrong-again']);
$ok1 = $fails === $max && $code === 429 && str_contains((string)($json['message'] ?? ''), 'locked');
printf("%s %d wrong passwords then the account is locked (got %d: %s)\n", $ok1 ? '✓' : '✗', $max, $code, $json['message'] ?? '');
exit($ok1 ? 0 : 1);
