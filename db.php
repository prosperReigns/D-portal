<?php
require_once 'utils/Logger.php';

class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $this->conn = mysqli_connect("localhost", "root", "", "cbt_app_db");
            
            if (!$this->conn) {
                throw new Exception("Database connection failed: " . mysqli_connect_error());
            }
            
            Logger::info('Database connection established');
        } catch (Exception $e) {
            Logger::error('Database connection error', ['message' => $e->getMessage()]);
            throw $e;
        }
    }
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function query($sql) {
        try {
            $result = mysqli_query($this->conn, $sql);
            if (!$result) {
                throw new Exception(mysqli_error($this->conn));
            }
            return $result;
        } catch (Exception $e) {
            Logger::error('Database query error', [
                'query' => $sql,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
?>