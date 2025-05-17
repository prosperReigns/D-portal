<?php
header('Content-Type: application/json');
require_once '../db.php';
require_once '../middleware/cors.php';
require_once '../middleware/rate_limit.php';
require_once '../utils/Logger.php';
require_once '../utils/ErrorHandler.php';
require_once '../utils/ApiResponse.php';
require_once '../vendor/autoload.php';
use Firebase\JWT\JWT;

try {
    // Initialize rate limiter
    $rateLimiter = new RateLimit(Database::getInstance()->getConnection());
    $clientIP = $_SERVER['REMOTE_ADDR'];
    $endpoint = 'login';

    // Check rate limit
    if (!$rateLimiter->checkLimit($clientIP, $endpoint)) {
        Logger::info('Rate limit exceeded', ['ip' => $clientIP, 'endpoint' => $endpoint]);
        echo ApiResponse::error('Rate limit exceeded. Please try again later.', 429);
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    $conn = Database::getInstance()->getConnection();
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate input
    if (!isset($data['username']) || !isset($data['password'])) {
        throw new Exception('Missing username or password');
    }

    // Sanitize inputs
    $username = mysqli_real_escape_string($conn, $data['username']);
    $password = $data['password'];

    // Check admin credentials
    $sql = "SELECT * FROM admins WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    
    if (!$stmt->execute()) {
        throw new Exception('Error checking credentials');
    }

    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    if ($admin && password_verify($password, $admin['password'])) {
        // Generate tokens
        $userData = [
            'id' => $admin['id'],
            'role' => 'admin'
        ];
        
        $tokens = TokenManager::generateTokens($userData);
        
        Logger::info('Admin login successful', ['admin_id' => $admin['id']]);
        
        echo ApiResponse::success([
            'admin_id' => $admin['id'],
            'username' => $admin['username'],
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_in' => $tokens['expires_in']
        ], 'Login successful');
    } else {
        Logger::info('Login failed - Invalid credentials', ['username' => $username]);
        throw new Exception('Invalid credentials');
    }

    // Generate JWT token
    $secret_key = "your_secret_key_here"; // Change this to a secure secret key
    $issued_at = time();
    $expiration_time = $issued_at + (60 * 60 * 24); // Token valid for 24 hours

    $payload = array(
        "iat" => $issued_at,
        "exp" => $expiration_time,
        "admin_id" => $admin['id'],
        "username" => $admin['username'],
        "role" => "admin"
    );

    $token = JWT::encode($payload, $secret_key, 'HS256');

    Logger::info('Admin logged in successfully', [
        'admin_id' => $admin['id'],
        'username' => $admin['username']
    ]);

    echo ApiResponse::success([
        'token' => $token,
        'admin' => [
            'id' => $admin['id'],
            'username' => $admin['username']
        ]
    ], 'Login successful');

} catch (Exception $e) {
    Logger::error('Login failed', [
        'endpoint' => 'login',
        'error' => $e->getMessage()
    ]);
    
    $statusCode = 500;
    if ($e->getMessage() === 'Invalid credentials' || $e->getMessage() === 'Missing username or password') {
        $statusCode = 401;
    } else if ($e->getMessage() === 'Method not allowed') {
        $statusCode = 405;
    }
    
    echo ApiResponse::error($e->getMessage(), $statusCode);
}