
<?php
class StreamHelper {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function generateStreamKey($user_id) {
        $key = 'live_' . $user_id . '_' . bin2hex(random_bytes(8));
        
        $query = "UPDATE live_streams SET stream_key = :key WHERE user_id = :user_id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute(['key' => $key, 'user_id' => $user_id]) ? $key : false;
    }

    public function getActiveStreams() {
        $query = "SELECT s.*, p.full_name 
                  FROM live_streams s 
                  JOIN profiles p ON s.user_id = p.user_id 
                  WHERE s.status = 'live'";
        return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }
}