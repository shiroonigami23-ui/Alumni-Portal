<?php
class Resource
{
    private $conn;
    private $table = "resources";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Create a new resource
     */
    public function create($user_id, $title, $description, $category, $file_url, $resource_type = 'document')
    {
        $query = "INSERT INTO " . $this->table . " 
                  (uploaded_by, title, description, category, file_url, resource_type, status) 
                  VALUES (:user_id, :title, :desc, :category, :url, :type, 'approved')
                  RETURNING resource_id";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            'user_id' => $user_id,
            'title' => $title,
            'desc' => $description,
            'category' => $category,
            'url' => $file_url,
            'type' => $resource_type
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['resource_id'] ?? false;
    }

    /**
     * Get all resources with filters
     */
    public function getAll($category = null, $type = null, $limit = 20, $offset = 0)
    {
        $where = ["r.status = 'approved'"];
        $params = ['limit' => $limit, 'offset' => $offset];

        if ($category) {
            $where[] = "r.category = :category";
            $params['category'] = $category;
        }

        if ($type) {
            $where[] = "r.resource_type = :type";
            $params['type'] = $type;
        }

        $whereClause = implode(' AND ', $where);

        $query = "SELECT r.*, p.full_name as uploader_name
                  FROM " . $this->table . " r
                  JOIN users u ON r.uploaded_by = u.user_id
                  JOIN profiles p ON u.user_id = p.user_id
                  WHERE $whereClause
                  ORDER BY r.created_at DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Track download
     */
    public function trackDownload($resource_id)
    {
        $query = "UPDATE " . $this->table . " SET download_count = download_count + 1 WHERE resource_id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute(['id' => $resource_id]);
    }
}
