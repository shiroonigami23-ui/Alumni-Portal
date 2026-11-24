<?php
/**
 * Retired: announcements are feed posts with post_type "announcement" (faculty/admin only, see create_post.php).
 * This legacy table was never shown anywhere, and the old endpoint let any member file entries.
 */
header('Content-Type: application/json; charset=UTF-8');
http_response_code(410);
echo json_encode(['success' => false, 'message' => 'This endpoint was retired. Faculty and admins post announcements from the feed (post type "announcement").', 'replacement' => 'api/create_post.php']);
