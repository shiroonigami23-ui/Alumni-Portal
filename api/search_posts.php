<?php
/**
 * Post search — same results, shape and visibility rules as the feed (it *is* the feed, filtered).
 * GET ?q=term[&page=N]
 */
require_once __DIR__ . '/_kernel.php';
$api = api_endpoint(['access' => 'member']);   // who may call this — enforced before anything below
if (trim((string)($_GET['q'] ?? '')) === '') {
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode(['success' => true, 'status' => 'success', 'data' => []]);
    exit;
}
$_GET['sort'] = $_GET['sort'] ?? 'newest';
$_GET['filter'] = 'all';
unset($_GET['user_id']);
chdir(__DIR__);
require __DIR__ . '/get_feed.php';
