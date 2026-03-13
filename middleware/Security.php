<?php

class Security
{
    private static function readAuthorizationHeader(): string
    {
        $candidates = [
            $_SERVER['HTTP_AUTHORIZATION'] ?? '',
            $_SERVER['Authorization'] ?? '',
            $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? ''
        ];

        foreach ($candidates as $value) {
            $value = trim((string)$value);
            if ($value !== '') {
                return $value;
            }
        }

        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders() ?: [];
        } elseif (function_exists('apache_request_headers')) {
            $headers = apache_request_headers() ?: [];
        }

        foreach ($headers as $key => $value) {
            if (strtolower((string)$key) === 'authorization') {
                $value = trim((string)$value);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }

    // Start session if not started
    public static function initSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Secure session params
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            // ini_set('session.cookie_secure', 1); // Enable if HTTPS is strictly enforced

            session_start();
        }
    }

    // Generate CSRF Token
    public static function generateCSRFToken()
    {
        self::initSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    // Validate CSRF Token
    public static function validateCSRFToken($token)
    {
        self::initSession();
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    // Checking Request for CSRF
    public static function checkCSRF()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // For authenticated API calls with Bearer token, CSRF is not required.
            $authorization = self::readAuthorizationHeader();
            if ($authorization !== '' && stripos($authorization, 'Bearer ') === 0) {
                return;
            }
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!self::validateCSRFToken($token)) {
                http_response_code(403);
                die(json_encode(['error' => 'Invalid CSRF Token']));
            }
        }
    }

    // XSS Prevention Helper
    public static function e($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    // Sanitize Input Array
    public static function sanitizeInput($input)
    {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = self::sanitizeInput($value);
            }
        } else {
            $input = self::e($input); // Basic escaping, logic might need strip_tags for HTML
        }
        return $input;
    }
}
