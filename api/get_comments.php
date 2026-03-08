<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

try {
    $viewer_id = $auth->validateRequest();
    $post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
    $since = isset($_GET['since']) ? trim((string)$_GET['since']) : '';

    if ($post_id <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "status" => "error", "message" => "Post ID required."]);
        exit;
    }

    $whereSince = '';
    $params = [':pid' => $post_id, ':viewer_id' => $viewer_id];
    if ($since !== '') {
        $whereSince = " AND c.updated_at > :since_ts";
        $params[':since_ts'] = $since;
    }

    $query = "SELECT
                c.comment_id,
                c.user_id,
                c.post_id,
                c.parent_comment_id,
                COALESCE(c.depth_level, 0) AS depth_level,
                c.content_file_path,
                COALESCE(c.reaction_count, 0) AS reaction_count,
                COALESCE(c.is_edited, false) AS is_edited,
                c.created_at,
                c.updated_at,
                u.role,
                u.email,
                p.full_name,
                p.profile_picture_url,
                EXISTS (
                    SELECT 1 FROM comment_reactions cr
                    WHERE cr.comment_id = c.comment_id AND cr.user_id = :viewer_id
                ) AS user_has_liked
              FROM comments c
              JOIN users u ON c.user_id = u.user_id
              LEFT JOIN profiles p ON c.user_id = p.user_id
              WHERE c.post_id = :pid $whereSince
              ORDER BY c.created_at DESC
              LIMIT 500";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($rows as $row) {
        $content = '';
        $attachments = [];
        $filePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, (string)$row['content_file_path']);
        if (is_file($filePath)) {
            $raw = file_get_contents($filePath);
            $decoded = json_decode((string)$raw, true);
            if (is_array($decoded) && array_key_exists('content', $decoded)) {
                $content = (string)($decoded['content'] ?? '');
                $attachments = is_array($decoded['attachments'] ?? null) ? $decoded['attachments'] : [];
            } else {
                $content = (string)$raw;
            }
        }

        $avatar = (string)($row['profile_picture_url'] ?? '');
        if ($avatar !== '') {
            $avatar = str_replace('\\', '/', $avatar);
        }

        $looksPlaceholder = (
            $avatar === '' ||
            stripos($avatar, 'via.placeholder.com') !== false ||
            stripos($avatar, 'placeholder') !== false ||
            stripos($avatar, 'data:image/svg+xml') === 0
        );
        if ($looksPlaceholder && ($row['role'] ?? '') === 'faculty') {
            $emailSlug = strtolower((string)($row['email'] ?? ''));
            $emailSlug = preg_replace('/[^a-z0-9]+/', '_', $emailSlug);
            $candidates = [
                "storage/profiles/faculty_{$emailSlug}.jpg",
                "storage/profiles/faculty_{$emailSlug}.jpeg",
                "storage/profiles/faculty_{$emailSlug}.png",
                "storage/profiles/faculty_{$emailSlug}.JPG",
                "storage/profiles/faculty_{$emailSlug}.webp",
            ];
            foreach ($candidates as $candidate) {
                $abs = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $candidate);
                if (file_exists($abs)) {
                    $avatar = $candidate;
                    break;
                }
            }
        }

        $userHasLikedVal = $row['user_has_liked'] ?? false;
        $userHasLiked = ($userHasLikedVal === true || $userHasLikedVal === 1 || $userHasLikedVal === '1' || $userHasLikedVal === 't');
        $data[] = [
            "id" => (int)$row['comment_id'],
            "post_id" => (int)$row['post_id'],
            "parent_comment_id" => isset($row['parent_comment_id']) ? (int)$row['parent_comment_id'] : null,
            "depth_level" => (int)($row['depth_level'] ?? 0),
            "author_id" => (int)$row['user_id'],
            "author_name" => (string)($row['full_name'] ?: 'RJIT Member'),
            "author_avatar" => $avatar,
            "content" => $content,
            "attachments" => $attachments,
            "likes_count" => (int)($row['reaction_count'] ?? 0),
            "user_has_liked" => $userHasLiked,
            "can_delete" => ((int)$row['user_id'] === (int)$viewer_id),
            "can_edit" => ((int)$row['user_id'] === (int)$viewer_id),
            "is_edited" => (($row['is_edited'] ?? false) === true || ($row['is_edited'] ?? false) === 1 || ($row['is_edited'] ?? false) === '1' || ($row['is_edited'] ?? false) === 't'),
            "created_at" => $row['created_at'],
            "updated_at" => $row['updated_at']
        ];
    }

    echo json_encode([
        "success" => true,
        "status" => "success",
        "data" => $data
    ]);
} catch (Throwable $e) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => "Unauthorized request."
    ]);
}
