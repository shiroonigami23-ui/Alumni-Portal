<?php
include_once __DIR__ . '/../config/Database.php';

$database = new Database();
$db = $database->getConnection();

// Blueprint Section 12.B: Find content archived over 30 days ago
$query = "SELECT archive_id, content_file_path FROM archived_posts 
          WHERE archived_at <= NOW() - INTERVAL '30 days' 
          AND content_deleted = false";

$stmt = $db->prepare($query);
$stmt->execute();
$to_purge = $stmt->fetchAll(PDO::FETCH_ASSOC);

$purge_count = 0;

foreach ($to_purge as $item) {
    $file_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . $item['content_file_path'];
    
    // Physically delete the file from the filesystem
    if (file_exists($file_path)) {
        if (unlink($file_path)) {
            // Mark as deleted in DB to prevent re-attempts
            $update = "UPDATE archived_posts SET content_deleted = true, content_deleted_at = NOW() WHERE archive_id = :aid";
            $u_stmt = $db->prepare($update);
            $u_stmt->execute(['aid' => $item['archive_id']]);
            $purge_count++;
        }
    }
}

echo "Cleanup Finished. Permanently deleted $purge_count legacy files.\n";
?>