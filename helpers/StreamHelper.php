
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
        // Delegates to the Stream model (never exposes stream keys)
        require_once __DIR__ . '/../models/Stream.php';
        return (new Stream($this->db))->active();
    }
}