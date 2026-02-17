<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../middleware/Auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$user_id = $auth->validateRequest();

try {
    // 1. Fetch Profile Data
    $stmt = $db->prepare("SELECT * FROM profiles WHERE user_id = :uid");
    $stmt->execute(['uid' => $user_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Fetch Posts
    $stmt = $db->prepare("SELECT content_file_path, created_at FROM posts WHERE user_id = :uid");
    $stmt->execute(['uid' => $user_id]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Create Export Directory
    $export_name = "export_" . $user_id . "_" . time();
    $export_path = dirname(__DIR__) . "/storage/exports/" . $export_name;
    if (!file_exists($export_path)) mkdir($export_path, 0777, true);

    // 4. Save Profile JSON
    file_put_contents($export_path . "/profile.json", json_encode($profile, JSON_PRETTY_PRINT));

    // 5. Package Posts Content
    foreach ($posts as $index => $post) {
        $source = dirname(__DIR__) . "/" . $post['content_file_path'];
        if (file_exists($source)) {
            copy($source, $export_path . "/post_" . $index . ".txt");
        }
    }

    // 6. Zip the Archive (GDPR Compliance Section 7.G)
    $zip_file = $export_path . ".zip";
    $zip = new ZipArchive();
    if ($zip->open($zip_file, ZipArchive::CREATE) === TRUE) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($export_path));
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($export_path) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();
    }

    echo json_encode([
        "message" => "Data export prepared successfully.",
        "download_url" => "storage/exports/" . $export_name . ".zip"
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Export failed: " . $e->getMessage()]);
}
?>