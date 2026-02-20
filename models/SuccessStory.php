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

        // category is accepted by API but this schema version has no category/status columns.
        try {
            $query = "INSERT INTO " . $this->table . " 
                      (alumni_user_id, title, story_content_file_path, featured_image_url, featured_by_admin_id, is_featured) 
                      VALUES (:user_id, :title, :path, :image, :featured_by, false)
                      ON CONFLICT (alumni_user_id)
                      DO UPDATE SET
                          title = EXCLUDED.title,
                          story_content_file_path = EXCLUDED.story_content_file_path,
                          featured_image_url = EXCLUDED.featured_image_url,
                          featured_by_admin_id = EXCLUDED.featured_by_admin_id,
                          is_featured = false,
                          updated_at = CURRENT_TIMESTAMP
                      RETURNING story_id";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                'user_id' => $user_id,
                'title' => $title,
                'path' => $relative_path,
                'image' => $featured_image,
                'featured_by' => $user_id
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['story_id'] ?? false;
        } catch (PDOException $e) {
            error_log("SuccessStory create error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all approved stories
     */
    public function getAll($limit = 20, $offset = 0, $category = null)
    {
        $query = "SELECT s.story_id,
                         s.alumni_user_id as user_id,
                         s.title,
                         s.story_content_file_path as content_file_path,
                         s.featured_image_url as featured_image,
                         s.is_featured,
                         s.display_order,
                         s.view_count,
                         s.created_at,
                         p.full_name,
                         p.profile_picture_url,
                         u.role
                  FROM " . $this->table . " s
                  JOIN users u ON s.alumni_user_id = u.user_id
                  JOIN profiles p ON u.user_id = p.user_id
                  ORDER BY s.created_at DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);

        $stmt->execute(['limit' => $limit, 'offset' => $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get story content from file
     */
    public function getContent($path)
    {
        $realPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
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
        $query = "UPDATE " . $this->table . " SET is_featured = true WHERE story_id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute(['id' => $story_id]);
    }
}
