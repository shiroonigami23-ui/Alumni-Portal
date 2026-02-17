<?php
class Logger {
    public static function log($user_id, $action, $details = "") {
        $logFile = __DIR__ . '/../storage/log.txt';
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        $entry = "[$timestamp] USER: $user_id | ACTION: $action | DETAILS: $details | IP: $ip" . PHP_EOL;
        
        file_put_contents($logFile, $entry, FILE_APPEND);
    }
}
