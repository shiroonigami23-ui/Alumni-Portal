<?php
/**
 * Retired legacy endpoint. It stored data outside the content store and skipped moderation,
 * so it no longer does anything. Kept so old clients get a clear answer instead of a 404.
 */
header('Content-Type: application/json; charset=UTF-8');
http_response_code(410);
echo json_encode(['success' => false, 'message' => 'This endpoint was retired. Use api/create_comment.php (with parent_comment_id) instead.', 'replacement' => 'api/create_comment.php (with parent_comment_id)']);
