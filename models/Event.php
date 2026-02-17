<?php
/**
 * Event Model
 * Handles all event-related database operations
 */

class Event {
    private $conn;
    private $table = 'events';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create a new event
     * 
     * @param int $creator_id User ID of event creator
     * @param string $title Event title
     * @param string $description Event description
     * @param string $event_date Event date (YYYY-MM-DD)
     * @param string $event_time Event time (HH:MM:SS)
     * @param string $end_date Event end date (optional)
     * @param string $end_time Event end time (optional)
     * @param string $location Physical location or virtual link
     * @param string $visibility 'public' or 'invite_only'
     * @param int $rsvp_limit Max attendees (optional)
     * @param string $banner_url Event banner image URL (optional)
     * @param string $live_stream_url Live stream URL (optional)
     * @param bool $comments_enabled Enable comments (default true)
     * @return int|false Event ID on success, false on failure
     */
    public function create($creator_id, $title, $description, $event_date, $event_time = '00:00:00', 
                          $end_date = null, $end_time = null, $location = null, 
                          $visibility = 'public', $rsvp_limit = null, $banner_url = null, 
                          $live_stream_url = null, $comments_enabled = true) {
        try {
            // Check user role to determine if approval needed
            $role_query = "SELECT role FROM users WHERE user_id = :user_id";
            $role_stmt = $this->conn->prepare($role_query);
            $role_stmt->execute(['user_id' => $creator_id]);
            $user = $role_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Faculty and Admin don't need approval
            $status = ($user['role'] === 'faculty' || $user['role'] === 'admin') 
                      ? 'approved' : 'pending_approval';

            $query = "INSERT INTO " . $this->table . " 
                     (creator_id, title, description, event_date, event_time, end_date, end_time, 
                      location, visibility, rsvp_limit, banner_url, live_stream_url, 
                      comments_enabled, status) 
                     VALUES 
                     (:creator_id, :title, :description, :event_date, :event_time, :end_date, :end_time, 
                      :location, :visibility::event_visibility, :rsvp_limit, :banner_url, :live_stream_url, 
                      :comments_enabled, :status::event_status)
                     RETURNING event_id";

            $stmt = $this->conn->prepare($query);
            
            $stmt->execute([
                'creator_id' => $creator_id,
                'title' => $title,
                'description' => $description,
                'event_date' => $event_date,
                'event_time' => $event_time,
                'end_date' => $end_date,
                'end_time' => $end_time,
                'location' => $location,
                'visibility' => $visibility,
                'rsvp_limit' => $rsvp_limit,
                'banner_url' => $banner_url,
                'live_stream_url' => $live_stream_url,
                'comments_enabled' => $comments_enabled,
                'status' => $status
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['event_id'];
            
        } catch (PDOException $e) {
            error_log("Event creation error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get event by ID
     */
    public function getById($event_id) {
        $query = "SELECT e.*, 
                         p.full_name as creator_name,
                         p.profile_picture_url as creator_avatar,
                         u.role as creator_role
                  FROM " . $this->table . " e
                  JOIN users u ON e.creator_id = u.user_id
                  JOIN profiles p ON u.user_id = p.user_id
                  WHERE e.event_id = :event_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['event_id' => $event_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all events with filters
     * 
     * @param string $filter 'upcoming', 'past', 'pending', 'all'
     * @param int $user_id Optional user ID to filter events created by user
     * @param int $limit Pagination limit
     * @param int $offset Pagination offset
     */
    public function getEvents($filter = 'upcoming', $user_id = null, $limit = 20, $offset = 0) {
        $where_clauses = ["e.status != 'cancelled'::event_status"];
        
        switch($filter) {
            case 'upcoming':
                $where_clauses[] = "e.event_date >= CURRENT_DATE";
                $where_clauses[] = "e.status = 'approved'::event_status";
                $order = "ORDER BY e.event_date ASC, e.event_time ASC";
                break;
            case 'past':
                $where_clauses[] = "e.event_date < CURRENT_DATE";
                $where_clauses[] = "e.status = 'approved'::event_status";
                $order = "ORDER BY e.event_date DESC, e.event_time DESC";
                break;
            case 'pending':
                $where_clauses[] = "e.status = 'pending_approval'::event_status";
                $order = "ORDER BY e.created_at DESC";
                break;
            case 'all':
                $order = "ORDER BY e.event_date DESC, e.event_time DESC";
                break;
            default:
                $where_clauses[] = "e.event_date >= CURRENT_DATE";
                $where_clauses[] = "e.status = 'approved'::event_status";
                $order = "ORDER BY e.event_date ASC, e.event_time ASC";
        }
        
        if ($user_id !== null) {
            $where_clauses[] = "e.creator_id = :user_id";
        }
        
        $where = "WHERE " . implode(" AND ", $where_clauses);
        
        $query = "SELECT e.*, 
                         p.full_name as creator_name,
                         p.profile_picture_url as creator_avatar,
                         u.role as creator_role,
                         e.rsvp_count,
                         e.waitlist_count
                  FROM " . $this->table . " e
                  JOIN users u ON e.creator_id = u.user_id
                  JOIN profiles p ON u.user_id = p.user_id
                  $where
                  $order
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        $params = [
            'limit' => $limit,
            'offset' => $offset
        ];
        
        if ($user_id !== null) {
            $params['user_id'] = $user_id;
        }
        
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update event
     */
    public function update($event_id, $data) {
        try {
            $allowed_fields = [
                'title', 'description', 'event_date', 'event_time', 
                'end_date', 'end_time', 'location', 'visibility', 
                'rsvp_limit', 'banner_url', 'live_stream_url', 'comments_enabled'
            ];
            
            $set_clauses = [];
            $params = ['event_id' => $event_id];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $allowed_fields)) {
                    if ($field === 'visibility') {
                        $set_clauses[] = "$field = :$field::event_visibility";
                    } else {
                        $set_clauses[] = "$field = :$field";
                    }
                    $params[$field] = $value;
                }
            }
            
            if (empty($set_clauses)) {
                return false;
            }
            
            $set = implode(", ", $set_clauses);
            $query = "UPDATE " . $this->table . " SET $set WHERE event_id = :event_id";
            
            $stmt = $this->conn->prepare($query);
            return $stmt->execute($params);
            
        } catch (PDOException $e) {
            error_log("Event update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete event
     */
    public function delete($event_id) {
        $query = "DELETE FROM " . $this->table . " WHERE event_id = :event_id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute(['event_id' => $event_id]);
    }

    /**
     * Approve event (Admin/Faculty only)
     */
    public function approve($event_id) {
        $query = "UPDATE " . $this->table . " 
                  SET status = 'approved'::event_status 
                  WHERE event_id = :event_id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute(['event_id' => $event_id]);
    }

    /**
     * Reject event
     */
    public function reject($event_id) {
        $query = "UPDATE " . $this->table . " 
                  SET status = 'rejected'::event_status 
                  WHERE event_id = :event_id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute(['event_id' => $event_id]);
    }

    /**
     * Cancel event
     */
    public function cancel($event_id) {
        $query = "UPDATE " . $this->table . " 
                  SET status = 'cancelled'::event_status 
                  WHERE event_id = :event_id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute(['event_id' => $event_id]);
    }

    /**
     * RSVP to event
     */
    public function rsvp($event_id, $user_id, $status = 'attending') {
        try {
            // Check if event has RSVP limit
            $event = $this->getById($event_id);
            
            if ($event['rsvp_limit'] && $event['rsvp_count'] >= $event['rsvp_limit']) {
                // Put on waitlist if full
                $status = 'waitlist';
            }
            
            $query = "INSERT INTO event_rsvps (event_id, user_id, rsvp_status) 
                     VALUES (:event_id, :user_id, :status::rsvp_status)
                     ON CONFLICT (event_id, user_id) 
                     DO UPDATE SET rsvp_status = :status::rsvp_status";
            
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                'event_id' => $event_id,
                'user_id' => $user_id,
                'status' => $status
            ]);
            
        } catch (PDOException $e) {
            error_log("RSVP error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancel RSVP
     */
    public function cancelRsvp($event_id, $user_id) {
        $query = "DELETE FROM event_rsvps WHERE event_id = :event_id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute(['event_id' => $event_id, 'user_id' => $user_id]);
    }

    /**
     * Get event attendees
     */
    public function getAttendees($event_id, $status = null) {
        $where = "WHERE er.event_id = :event_id";
        
        if ($status) {
            $where .= " AND er.rsvp_status = :status::rsvp_status";
        }
        
        $query = "SELECT er.rsvp_status, er.created_at as rsvp_at,
                         u.user_id, p.full_name, p.profile_picture_url, u.role
                  FROM event_rsvps er
                  JOIN users u ON er.user_id = u.user_id
                  JOIN profiles p ON u.user_id = p.user_id
                  $where
                  ORDER BY er.created_at ASC";
        
        $stmt = $this->conn->prepare($query);
        $params = ['event_id' => $event_id];
        
        if ($status) {
            $params['status'] = $status;
        }
        
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if user has RSVP'd to event
     */
    public function hasRsvp($event_id, $user_id) {
        $query = "SELECT rsvp_status FROM event_rsvps 
                  WHERE event_id = :event_id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['event_id' => $event_id, 'user_id' => $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get events user is attending
     */
    public function getUserEvents($user_id, $filter = 'upcoming') {
        $date_filter = ($filter === 'upcoming') 
            ? "e.event_date >= CURRENT_DATE" 
            : "e.event_date < CURRENT_DATE";
        
        $query = "SELECT e.*, 
                         p.full_name as creator_name,
                         p.profile_picture_url as creator_avatar,
                         u.role as creator_role,
                         er.rsvp_status
                  FROM event_rsvps er
                  JOIN " . $this->table . " e ON er.event_id = e.event_id
                  JOIN users u ON e.creator_id = u.user_id
                  JOIN profiles p ON u.user_id = p.user_id
                  WHERE er.user_id = :user_id 
                    AND e.status = 'approved'::event_status
                    AND $date_filter
                  ORDER BY e.event_date ASC, e.event_time ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}