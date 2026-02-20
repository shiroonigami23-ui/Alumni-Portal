<?php
/**
 * Tech Stack Helper
 * Handles tech stack operations between normalized user_tech_stats table and display format
 */

class TechStackHelper {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get user's tech stack as comma-separated string
     * 
     * @param int $user_id User ID
     * @return string Comma-separated tech skills (e.g., "PHP, JavaScript, Python")
     */
    public function getUserTechStack($user_id) {
        try {
            $query = "SELECT us.skill_name
                     FROM user_tech_stats us
                     WHERE us.user_id = :user_id
                     ORDER BY us.skill_name ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute(['user_id' => $user_id]);
            
            $skills = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            return !empty($skills) ? implode(', ', $skills) : null;
            
        } catch (PDOException $e) {
            error_log("Tech stack fetch error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get user's tech stack as array
     * 
     * @param int $user_id User ID
     * @return array Array of skill names
     */
    public function getUserTechStackArray($user_id) {
        try {
            $query = "SELECT us.skill_name
                     FROM user_tech_stats us
                     WHERE us.user_id = :user_id
                     ORDER BY us.skill_name ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute(['user_id' => $user_id]);
            
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
            
        } catch (PDOException $e) {
            error_log("Tech stack fetch error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Set user's tech stack from comma-separated string or array
     * 
     * @param int $user_id User ID
     * @param string|array $tech_stack Comma-separated string or array of skills
     * @return bool Success status
     */
    public function setUserTechStack($user_id, $tech_stack) {
        try {
            // Convert to array if string
            if (is_string($tech_stack)) {
                $skills = array_map('trim', explode(',', $tech_stack));
            } else {
                $skills = $tech_stack;
            }
            
            // Remove empty values
            $skills = array_filter($skills);
            
            if (empty($skills)) {
                return true;
            }
            
            // Start transaction
            $this->conn->beginTransaction();
            
            // Clear existing skills
            $delete_query = "DELETE FROM user_tech_stats WHERE user_id = :user_id";
            $delete_stmt = $this->conn->prepare($delete_query);
            $delete_stmt->execute(['user_id' => $user_id]);
            
            // Insert each skill
            foreach ($skills as $skill_name) {
                $skill_name = trim($skill_name);
                if (empty($skill_name)) continue;

                $insert_query = "INSERT INTO user_tech_stats (user_id, skill_name) 
                               VALUES (:user_id, :skill_name)
                               ON CONFLICT (user_id, skill_name) DO NOTHING";
                $insert_stmt = $this->conn->prepare($insert_query);
                $insert_stmt->execute([
                    'user_id' => $user_id,
                    'skill_name' => $skill_name
                ]);
            }
            
            $this->conn->commit();
            return true;
            
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Tech stack set error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get or create a tech skill
     * 
     * @param string $skill_name Skill name
     * @return int|false Skill ID or false on failure
     */
    private function getOrCreateSkill($skill_name) {
        try {
            // Try to get existing skill
            $query = "SELECT skill_id FROM tech_skills WHERE LOWER(skill_name) = LOWER(:skill_name)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute(['skill_name' => $skill_name]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return $result['skill_id'];
            }
            
            // Create new skill
            $insert_query = "INSERT INTO tech_skills (skill_name) 
                           VALUES (:skill_name) 
                           RETURNING skill_id";
            $insert_stmt = $this->conn->prepare($insert_query);
            $insert_stmt->execute(['skill_name' => $skill_name]);
            $result = $insert_stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['skill_id'];
            
        } catch (PDOException $e) {
            error_log("Get/create skill error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add single skill to user
     * 
     * @param int $user_id User ID
     * @param string $skill_name Skill name
     * @return bool Success status
     */
    public function addSkill($user_id, $skill_name) {
        try {
            $query = "INSERT INTO user_tech_stats (user_id, skill_name) 
                     VALUES (:user_id, :skill_name)
                     ON CONFLICT (user_id, skill_name) DO NOTHING";
            
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                'user_id' => $user_id,
                'skill_name' => trim($skill_name)
            ]);
            
        } catch (PDOException $e) {
            error_log("Add skill error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove skill from user
     * 
     * @param int $user_id User ID
     * @param string $skill_name Skill name
     * @return bool Success status
     */
    public function removeSkill($user_id, $skill_name) {
        try {
            $query = "DELETE FROM user_tech_stats 
                     WHERE user_id = :user_id 
                     AND LOWER(skill_name) = LOWER(:skill_name)";
            
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                'user_id' => $user_id,
                'skill_name' => $skill_name
            ]);
            
        } catch (PDOException $e) {
            error_log("Remove skill error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Search users by skill
     * 
     * @param string $skill_name Skill name to search for
     * @return array Array of user IDs
     */
    public function searchUsersBySkill($skill_name) {
        try {
            $query = "SELECT DISTINCT us.user_id 
                     FROM user_tech_stats us
                     WHERE LOWER(us.skill_name) LIKE LOWER(:skill_name)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute(['skill_name' => '%' . $skill_name . '%']);
            
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
            
        } catch (PDOException $e) {
            error_log("Search by skill error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all available skills
     * 
     * @param int $limit Limit results
     * @return array Array of skill names
     */
    public function getAllSkills($limit = 100) {
        try {
            $query = "SELECT skill_name FROM tech_skills ORDER BY skill_name ASC LIMIT :limit";
            $stmt = $this->conn->prepare($query);
            $stmt->execute(['limit' => $limit]);
            
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
            
        } catch (PDOException $e) {
            error_log("Get all skills error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get popular skills (most used)
     * 
     * @param int $limit Number of top skills to return
     * @return array Array of skills with usage count
     */
    public function getPopularSkills($limit = 20) {
        try {
            $query = "SELECT us.skill_name, COUNT(us.user_id) as user_count
                     FROM user_tech_stats us
                     GROUP BY us.skill_name
                     ORDER BY user_count DESC, us.skill_name ASC
                     LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute(['limit' => $limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get popular skills error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Bulk add tech stack for multiple users (useful for imports)
     * 
     * @param array $user_tech_stats_map Array of [user_id => "skill1, skill2, skill3"]
     * @return array Results array with success/failure per user
     */
    public function bulkSetTechStack($user_tech_stats_map) {
        $results = [];
        
        foreach ($user_tech_stats_map as $user_id => $tech_stack) {
            $results[$user_id] = $this->setUserTechStack($user_id, $tech_stack);
        }
        
        return $results;
    }
}
