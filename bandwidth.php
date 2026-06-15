<?php

class BandwidthManager {
    private $dbPath;
    private $db;
    
    // Limits in bytes
    const DAILY_LIMIT = 524288000;   // 500 MB
    const WEEKLY_LIMIT = 1073741824; // 1 GB
    
    public function __construct($dbPath = null) {
        if ($dbPath === null) {
            $this->dbPath = __DIR__ . '/db/bandwidth.sqlite';
        } else {
            $this->dbPath = $dbPath;
        }
        
        $dbDir = dirname($this->dbPath);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }
        
        // Ensure the directory is writable
        if (!is_writable($dbDir)) {
            chmod($dbDir, 0755);
        }
        
        $this->db = new SQLite3($this->dbPath);
        $this->db->busyTimeout(5000);
        $this->init();
    }
    
    private function init() {
        $query = "
            CREATE TABLE IF NOT EXISTS bandwidth_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ip TEXT NOT NULL,
                bytes INTEGER NOT NULL,
                file_name TEXT NOT NULL,
                timestamp INTEGER NOT NULL
            );
            CREATE INDEX IF NOT EXISTS idx_bandwidth_logs_ip_timestamp ON bandwidth_logs(ip, timestamp);
        ";
        $this->db->exec($query);
    }
    
    /**
     * Get client IP address accurately
     */
    public static function getClientIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        }
    }
    
    /**
     * Get bandwidth usage stats for a specific IP
     */
    public function getUsage($ip) {
        $now = time();
        $oneDayAgo = $now - 86400;
        $oneWeekAgo = $now - 604800;
        
        // Get daily usage
        $stmt = $this->db->prepare("SELECT SUM(bytes) FROM bandwidth_logs WHERE ip = :ip AND timestamp >= :cutoff");
        $stmt->bindValue(':ip', $ip, SQLITE3_TEXT);
        $stmt->bindValue(':cutoff', $oneDayAgo, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $row = $res->fetchArray(SQLITE3_NUM);
        $dailyBytes = (int)($row[0] ?? 0);
        $res->finalize();
        
        // Get weekly usage
        $stmt = $this->db->prepare("SELECT SUM(bytes) FROM bandwidth_logs WHERE ip = :ip AND timestamp >= :cutoff");
        $stmt->bindValue(':ip', $ip, SQLITE3_TEXT);
        $stmt->bindValue(':cutoff', $oneWeekAgo, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $row = $res->fetchArray(SQLITE3_NUM);
        $weeklyBytes = (int)($row[0] ?? 0);
        $res->finalize();
        
        return [
            'ip' => $ip,
            'daily_bytes' => $dailyBytes,
            'weekly_bytes' => $weeklyBytes,
            'daily_limit' => self::DAILY_LIMIT,
            'weekly_limit' => self::WEEKLY_LIMIT,
            'daily_percentage' => min(100, round(($dailyBytes / self::DAILY_LIMIT) * 100, 1)),
            'weekly_percentage' => min(100, round(($weeklyBytes / self::WEEKLY_LIMIT) * 100, 1))
        ];
    }
    
    /**
     * Log a successful bandwidth request and prune old logs
     */
    public function logRequest($ip, $fileName, $bytes) {
        $stmt = $this->db->prepare("INSERT INTO bandwidth_logs (ip, file_name, bytes, timestamp) VALUES (:ip, :file_name, :bytes, :timestamp)");
        $stmt->bindValue(':ip', $ip, SQLITE3_TEXT);
        $stmt->bindValue(':file_name', $fileName, SQLITE3_TEXT);
        $stmt->bindValue(':bytes', $bytes, SQLITE3_INTEGER);
        $stmt->bindValue(':timestamp', time(), SQLITE3_INTEGER);
        $stmt->execute();
        
        // 1 in 20 requests cleans up logs older than 7 days
        if (rand(1, 20) === 1) {
            $cutoff = time() - 604800;
            $cleanupStmt = $this->db->prepare("DELETE FROM bandwidth_logs WHERE timestamp < :cutoff");
            $cleanupStmt->bindValue(':cutoff', $cutoff, SQLITE3_INTEGER);
            $cleanupStmt->execute();
        }
    }
}

/**
 * Common formatting utility
 */
function formatBandwidthBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}
