<?php

function ensure_password_reset_schema(PDO $db): void
{
    $db->exec("
        CREATE TABLE IF NOT EXISTS password_resets (
            reset_id BIGSERIAL PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            token VARCHAR(255),
            token_hash VARCHAR(255),
            expires_at TIMESTAMP WITHOUT TIME ZONE,
            used_at TIMESTAMP WITHOUT TIME ZONE,
            requested_ip VARCHAR(64),
            user_agent TEXT,
            created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $db->exec("ALTER TABLE password_resets ADD COLUMN IF NOT EXISTS token_hash VARCHAR(255)");
    $db->exec("ALTER TABLE password_resets ADD COLUMN IF NOT EXISTS expires_at TIMESTAMP WITHOUT TIME ZONE");
    $db->exec("ALTER TABLE password_resets ADD COLUMN IF NOT EXISTS used_at TIMESTAMP WITHOUT TIME ZONE");
    $db->exec("ALTER TABLE password_resets ADD COLUMN IF NOT EXISTS requested_ip VARCHAR(64)");
    $db->exec("ALTER TABLE password_resets ADD COLUMN IF NOT EXISTS user_agent TEXT");
    $db->exec("ALTER TABLE password_resets ADD COLUMN IF NOT EXISTS created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_password_resets_email ON password_resets(email)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_password_resets_token_hash ON password_resets(token_hash)");
}

function password_reset_base_url(): string
{
    $configured = trim((string)(getenv('APP_BASE_URL') ?: ''));
    if ($configured !== '') {
        return rtrim($configured, '/');
    }

    $scheme = 'http';
    $forwardedProto = trim((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    if ($forwardedProto !== '') {
        $scheme = strtolower(trim((string)explode(',', $forwardedProto)[0]));
    } elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $scheme = 'https';
    }

    $host = trim((string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $scriptDir = str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '')));
    $basePath = preg_replace('#/api$#', '', rtrim($scriptDir, '/'));

    return rtrim($scheme . '://' . $host . $basePath, '/');
}
