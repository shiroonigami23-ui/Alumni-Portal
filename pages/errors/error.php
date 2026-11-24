<?php
/**
 * Friendly error page used by the router/security layer (404, 405, 413, 415, 429 …).
 * Self-contained: no database, no session, works even when everything else is down.
 */
$status = (int)($GLOBALS['ap_error_status'] ?? http_response_code() ?: 404);
$message = (string)($GLOBALS['ap_error_message'] ?? 'Something went wrong.');
$base = function_exists('ap_url') ? rtrim(ap_url(''), '/') : '';
$titles = [
    400 => 'That link looks broken', 403 => 'Access denied', 404 => 'Page not found',
    405 => 'Not allowed', 413 => 'File too large', 415 => 'File blocked',
    429 => 'Slow down a little', 500 => 'Something broke', 503 => 'Back in a moment',
];
$title = $titles[$status] ?? 'Something went wrong';
$e = fn ($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$links = [
    ['Home', 'index.php', 'M3 12l9-9 9 9M5 10v10h14V10'],
    ['Alumni feed', 'feed.php', 'M4 6h16M4 12h16M4 18h10'],
    ['Find alumni', 'discovery.php', 'M11 4a7 7 0 100 14 7 7 0 000-14zM21 21l-4.3-4.3'],
    ['Mentorship', 'mentorship.php', 'M12 14l9-5-9-5-9 5 9 5zM12 14v7'],
    ['Jobs', 'jobs.php', 'M4 7h16v12H4zM9 7V5h6v2'],
    ['Help centre', 'help.php', 'M12 18h.01M9 9a3 3 0 115 2c-1 1-2 1.5-2 3'],
];
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title><?= $status ?> · <?= $e($title) ?> · RJIT Alumni Portal</title>
<link rel="icon" href="<?= $e($base) ?>/assets/icons/app-icon-192.png">
<style>
:root{--ink:#0f172a;--muted:#64748b;--line:#e2e8f0;--brand:#2563eb;--brand2:#7c3aed;--bg:#f8fafc}
@media (prefers-color-scheme:dark){:root{--ink:#e2e8f0;--muted:#94a3b8;--line:#1e293b;--bg:#020617}}
*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;background:var(--bg);color:var(--ink);font:16px/1.55 Inter,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;padding:24px}
.card{max-width:560px;width:100%;text-align:center}
.code{font-size:96px;font-weight:800;letter-spacing:-4px;line-height:1;background:linear-gradient(135deg,var(--brand),var(--brand2));-webkit-background-clip:text;background-clip:text;color:transparent;animation:float 4s ease-in-out infinite}
@keyframes float{50%{transform:translateY(-6px)}}
@media (prefers-reduced-motion:reduce){.code{animation:none}}
h1{font-size:24px;margin:12px 0 6px}p{color:var(--muted);margin:0 auto 28px;max-width:440px}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin-bottom:24px}
a.tile{display:flex;gap:10px;align-items:center;padding:12px 14px;border:1px solid var(--line);border-radius:12px;color:var(--ink);text-decoration:none;font-weight:500;font-size:14px;transition:border-color .15s,transform .15s}
a.tile:hover,a.tile:focus-visible{border-color:var(--brand);transform:translateY(-1px);outline:none}
svg{width:18px;height:18px;flex:none;stroke:var(--brand);fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.back{background:none;border:0;color:var(--brand);font:inherit;font-weight:600;cursor:pointer}
</style>
</head>
<body>
<main class="card" role="main">
    <div class="code" aria-hidden="true"><?= $status ?></div>
    <h1><?= $e($title) ?></h1>
    <p><?= $e($message) ?></p>
    <?php if (in_array($status, [404, 403, 400], true)): ?>
    <nav class="grid" aria-label="Helpful links">
        <?php foreach ($links as [$label, $href, $d]): ?>
            <a class="tile" href="<?= $e($base . '/' . $href) ?>"><svg viewBox="0 0 24 24"><path d="<?= $d ?>"/></svg><?= $e($label) ?></a>
        <?php endforeach; ?>
    </nav>
    <?php endif; ?>
    <button class="back" onclick="history.length > 1 ? history.back() : location.href='<?= $e($base) ?>/'">← Go back</button>
</main>
</body>
</html>
