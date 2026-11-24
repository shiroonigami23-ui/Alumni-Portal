<?php
class RateLimiter {
    public static function check($db, $ip, $action, $limit = 5, $minutes = 15) {
        $query = "SELECT COUNT(*) FROM activity_logs 
                  WHERE ip_address = :ip AND action = :action 
                  AND timestamp > NOW() - (CAST(" . max(1, (int)$minutes) . " AS INTEGER) * INTERVAL '1 minute')";
        $stmt = $db->prepare($query);
        $stmt->execute(['ip' => $ip, 'action' => $action]);
        return $stmt->fetchColumn() < $limit;
    }
}