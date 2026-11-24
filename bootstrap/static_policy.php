<?php
/**
 * Which static files may be served to browsers. Mirrors the rules in .htaccess
 * so `php -S localhost:8088 router.php` is exactly as locked down as Apache.
 */
function ap_static_allowed(string $rel): bool
{
    $rel = ltrim(str_replace('\\', '/', $rel), '/');

    // Hidden files (.env, .git, .htaccess …)
    if (preg_match('#(^|/)\.#', $rel) && strpos($rel, '.well-known/') !== 0) {
        return false;
    }
    // Source, config, infra and internal folders are never web-visible
    if (preg_match('#^(bootstrap|config|helpers|models|middleware|cron|scripts|tests|deployment|terraform|vendor|tools|docs|includes/[^/]+\.php)(/|$)|(^|/)node_modules(/|$)#', $rel)) {
        return false;
    }
    // Private user data in storage
    if (preg_match('#^storage/(verification|exports|logs|cache|messages|imports|test_reports|resumes)(/|$)#', $rel)) {
        return false;
    }
    // In storage, only media/documents — never text/json/archives/code
    if (strpos($rel, 'storage/') === 0 && !preg_match('/\.(jpe?g|png|gif|webp|pdf|docx?|mp3|mp4|m4a|webm)$/i', $rel)) {
        return false;
    }
    // Docs, dumps, infra files anywhere
    if (preg_match('/\.(md|sql|sh|bat|ps1|py|tf|tfvars|ya?ml|lock|ini|log|bak|dist|env|txt)$/i', $rel)
        || preg_match('#(^|/)(composer\.json|Dockerfile|docker-compose\.yml)$#i', $rel)
        || preg_match('#^docs/.+\.json$#i', $rel)) {
        return false;
    }
    return true;
}
