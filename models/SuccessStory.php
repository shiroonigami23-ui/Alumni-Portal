<?php
class SuccessStory
{
    private $conn;
    private $table = "success_stories";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Create a new success story
     */
    public function create($user_id, $title, $content, $category = 'career', $featured_image = null)
    {
        // Store content in file (3.5NF architecture)
        $filename = "story_" . $user_id . "_" . time() . ".txt";
        $storage_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . "storage" . DIRECTORY_SEPARATOR . "stories" . DIRECTORY_SEPARATOR;

        if (!file_exists($storage_dir)) {
            mkdir($storage_dir, 0777, true);
        }

        file_put_contents($storage_dir . $filename, $content);
        $relative_path = "storage/stories/" . $filename;

        $query = "INSERT INTO " . $this->table . " 
                  (user_id, title, content_file_path, category, featured_image, status) 
                  VALUES (:user_id, :title, :path, :category, :image, 'pending')
                  RETURNING story_id";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            'user_id' => $user_id,
            'title' => $title,
            'path' => $relative_path,
            'category' => $category,
            'image' => $featured_image
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['story_id'] ?? false;
    }

    /**
     * Get all approved stories
     */
    public function getAll($limit = 20, $offset = 0, $category = null)
    {
        $where = "WHERE s.status = 'approved'";

        if ($category) {
            $where .= " AND s.category = :category";
        }

        $query = "SELECT s.*, p.full_name, p.profile_picture_url, u.role
                  FROM " . $this->table . " s
                  JOIN users u ON s.user_id = u.user_id
                  JOIN profiles p ON u.user_id = p.user_id
                  $where
                  ORDER BY s.created_at DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);

        $params = ['limit' => $limit, 'offset' => $offset];
        if ($category) {
            $params['category'] = $category;
        }

        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get story content from file
     */
    public function getContent($path)
    {
        $realPath = str_replace('/', DIRECTORY_SEPARATOR, $path);
        if (file_exists($realPath)) {
            return file_get_contents($realPath);
        }
        return "Content not available.";
    }

    /**
     * Approve story (Admin only)
     */
    public function approve($story_id)
    {
        $query = "UPDATE " . $this->table . " SET status = 'approved' WHERE story_id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute(['id' => $story_id]);
    }
}
