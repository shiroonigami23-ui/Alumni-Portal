<?php

class Security
{

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
