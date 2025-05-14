<?php
class RateLimit {
    private $conn;
    private $window = 3600; // 1 hour window
    private $limit = 100;   // 100 requests per hour

    public function __construct($conn) {
        $this->conn = $conn;
        
        // Create rate_limits table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS rate_limits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            endpoint VARCHAR(255) NOT NULL,
            requests INT DEFAULT 1,
            window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ip_endpoint (ip_address, endpoint)
        )";
        mysqli_query($this->conn, $sql);
    }

    public function checkLimit($ip, $endpoint) {
        // Clean up old records
        $cleanup = "DELETE FROM rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        mysqli_query($this->conn, $cleanup);

        // Check existing rate limit
        $sql = "SELECT * FROM rate_limits WHERE ip_address = ? AND endpoint = ? AND window_start > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $ip, $endpoint);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);

        if ($row) {
            if ($row['requests'] >= $this->limit) {
                return false; // Rate limit exceeded
            }

            // Increment request count
            $update = "UPDATE rate_limits SET requests = requests + 1 WHERE id = ?";
            $stmt = mysqli_prepare($this->conn, $update);
            mysqli_stmt_bind_param($stmt, "i", $row['id']);
            mysqli_stmt_execute($stmt);
        } else {
            // Create new rate limit record
            $insert = "INSERT INTO rate_limits (ip_address, endpoint) VALUES (?, ?)";
            $stmt = mysqli_prepare($this->conn, $insert);
            mysqli_stmt_bind_param($stmt, "ss", $ip, $endpoint);
            mysqli_stmt_execute($stmt);
        }

        return true;
    }
}