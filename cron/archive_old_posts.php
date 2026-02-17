<?php
include_once __DIR__ . '/../config/Database.php';

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();

    // Mapping active post columns to archive columns (Blueprint Section 12)
    $query = "INSERT INTO archived_posts (
                original_post_id, user_id, post_type, content_file_path, 
                final_reaction_count, final_comment_count, final_view_count, 
                original_created_at
              )
              SELECT 
                post_id, user_id, post_type, content_file_path, 
                reaction_count, comment_count, view_count, 
                created_at 
              FROM posts 
              WHERE created_at <= NOW() - INTERVAL '7 days'";
    
    $db->exec($query);

    // Clean up active table
    $delete = "DELETE FROM posts WHERE created_at <= NOW() - INTERVAL '7 days'";
    $count = $db->exec($delete);

    $db->commit();
    echo "Archival Finished. Moved $count posts to the archive storage.\n";

} catch (Exception $e) {
    $db->rollBack();
    echo "Archival Failed: " . $e->getMessage() . "\n";
}
?>