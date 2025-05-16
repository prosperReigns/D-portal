<?php
require_once '../utils/Logger.php';
require_once '../utils/ApiResponse.php';

class RateLimit {
    private $conn;
    private $window = 3600; // 1 hour window
    private $limit = 100;   // 100 requests per hour
    private $cleanup_probability = 0.1; // 10% chance to run cleanup

    public function __construct($conn) {
        $this->conn = $conn;
        
        // Create rate_limits table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS rate_limits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            endpoint VARCHAR(255) NOT NULL,
            requests INT DEFAULT 1,
            window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ip_endpoint (ip_address, endpoint),
            INDEX idx_window_start (window_start)
        )";
        mysqli_query($this->conn, $sql);
        
        // Random cleanup of old records
        if (rand(0, 100) / 100 < $this->cleanup_probability) {
            $this->cleanupOldRecords();
        }
    }

    private function cleanupOldRecords() {
        $cleanup = "DELETE FROM rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        mysqli_query($this->conn, $cleanup);
        Logger::info('Rate limit cleanup performed');
    }

    public function checkLimit($ip, $endpoint) {
        try {
            // Check existing rate limit
            $sql = "SELECT * FROM rate_limits WHERE ip_address = ? AND endpoint = ? AND window_start > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "ss", $ip, $endpoint);
            
            if (!$stmt->execute()) {
                throw new Exception('Database error while checking rate limit');
            }

            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);

            if ($row) {
                if ($row['requests'] >= $this->limit) {
                    Logger::warning('Rate limit exceeded', [
                        'ip' => $ip,
                        'endpoint' => $endpoint,
                        'requests' => $row['requests']
                    ]);
                    return false;
                }

                // Increment request count
                $update = "UPDATE rate_limits SET requests = requests + 1 WHERE id = ?";
                $stmt = mysqli_prepare($this->conn, $update);
                mysqli_stmt_bind_param($stmt, "i", $row['id']);
                
                if (!$stmt->execute()) {
                    throw new Exception('Database error while updating rate limit');
                }
            } else {
                // Create new rate limit record
                $insert = "INSERT INTO rate_limits (ip_address, endpoint) VALUES (?, ?)";
                $stmt = mysqli_prepare($this->conn, $insert);
                mysqli_stmt_bind_param($stmt, "ss", $ip, $endpoint);
                
                if (!$stmt->execute()) {
                    throw new Exception('Database error while creating rate limit');
                }
            }

            return true;

        } catch (Exception $e) {
            Logger::error('Rate limit error', [
                'ip' => $ip,
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);
            return true; // On error, allow the request to prevent blocking legitimate users
        }
    }
}