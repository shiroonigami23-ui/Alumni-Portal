<?php
include_once __DIR__ . "/../config/Database.php";

$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM scheduled_posts WHERE publish_at <= NOW()";
$stmt = $db->prepare($query);
$stmt->execute();
$ready_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$published_count = 0;

foreach ($ready_posts as $post) {
    try {
        $db->beginTransaction();

        $insert = "INSERT INTO posts (user_id, content_file_path, post_type, created_at) 
                   VALUES (:uid, :path, :type, :created_at)";
        $i_stmt = $db->prepare($insert);
        $i_stmt->execute([
            ":uid" => $post["user_id"],
            ":path" => $post["content_path"],
            ":type" => $post["type"],
            ":created_at" => $post["publish_at"]
        ]);

        $delete = "DELETE FROM scheduled_posts WHERE scheduled_id = :sid";
        $d_stmt = $db->prepare($delete);
        $d_stmt->execute([":sid" => $post["scheduled_id"]]);

        $db->commit();
        $published_count++;
    } catch (Exception $e) {
        $db->rollBack();
        echo "Error: " . $e->getMessage() . "\n";
    }
}
echo "Cron Job Finished. Published $published_count posts.\n";
?>
