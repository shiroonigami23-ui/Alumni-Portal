<?php
class Post {
    private $conn;
    private $table_name = "posts";

    public function __construct($db) {
        $this->conn = $db;
    }

    // READ PUBLIC POSTS (Matching your specific 3.5NF Schema)
    public function readPublic() {
        // Using the exact column names from your \d posts output
        $query = "SELECT 
                    p.post_id, 
                    p.user_id, 
                    p.post_type, 
                    p.content_file_path, 
                    p.title, 
                    p.reaction_count, 
                    p.comment_count, 
                    p.created_at, 
                    u.email as author, 
                    u.role 
                  FROM " . $this->table_name . " p
                  LEFT JOIN users u ON p.user_id = u.user_id
                  WHERE p.status = 'published'
                  ORDER BY p.is_pinned DESC, p.created_at DESC 
                  LIMIT 20";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // HELPER: Read content from local file system (Blueprint: .txt files)
    public function getFileContent($path) {
        // Convert any forward slashes to backslashes for Windows
        $realPath = str_replace('/', DIRECTORY_SEPARATOR, $path);
        
        if (!empty($realPath) && file_exists($realPath)) {
            $data = file_get_contents($realPath);
            return $data ?: "File is empty.";
        }
        
        // Debugging: return the path it tried to open if it fails
        return "Error: Could not find file at " . $realPath;
    }
}
?>