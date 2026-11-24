<?php
/**
 * Retired: this legacy endpoint had no role check (any signed-in user could ban anyone).
 * Bans and suspensions go through api/admin_user_actions.php, which is admin-only and audited.
 */
header('Content-Type: application/json; charset=UTF-8');
http_response_code(410);
echo json_encode(['success' => false, 'message' => 'This endpoint was retired. Use api/admin_user_actions.php instead.', 'replacement' => 'api/admin_user_actions.php']);
