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
    $endpoint = 'register';

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

    // Validate required fields
    if (!isset($data['name'], $data['class'], $data['subject'], $data['test_title'])) {
        throw new Exception('Missing required fields');
    }

    // Sanitize inputs
    $name = mysqli_real_escape_string($conn, $data['name']);
    $class = mysqli_real_escape_string($conn, $data['class']);
    $subject = mysqli_real_escape_string($conn, $data['subject']);
    $test_title = mysqli_real_escape_string($conn, $data['test_title']);

    // Check if test exists
    $test_query = "SELECT id FROM tests WHERE title = ? AND class = ? AND subject = ?";
    $stmt = $conn->prepare($test_query);
    $stmt->bind_param("sss", $test_title, $class, $subject);
    
    if (!$stmt->execute()) {
        throw new Exception('Error checking test existence');
    }

    $test_result = $stmt->get_result();
    if ($test_result->num_rows === 0) {
        Logger::info('Test not found', [
            'test_title' => $test_title,
            'class' => $class,
            'subject' => $subject
        ]);
        throw new Exception('Test not available for registration');
    }

    // Register student
    $insert_sql = "INSERT INTO students (name, class) VALUES (?, ?)";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("ss", $name, $class);
    
    if (!$stmt->execute()) {
        throw new Exception('Error registering student');
    }
    
    $student_id = $stmt->insert_id;
    
    // Generate tokens
    $userData = [
        'id' => $student_id,
        'role' => 'student'
    ];
    
    $tokens = TokenManager::generateTokens($userData);
    
    Logger::info('Student registered successfully', [
        'student_id' => $student_id,
        'class' => $class
    ]);
    
    echo ApiResponse::success([
        'student_id' => $student_id,
        'access_token' => $tokens['access_token'],
        'refresh_token' => $tokens['refresh_token'],
        'expires_in' => $tokens['expires_in'],
        'test_details' => [
            'title' => $test_title,
            'class' => $class,
            'subject' => $subject
        ]
    ], 'Registration successful');

} catch (Exception $e) {
    Logger::error('Registration failed', [
        'endpoint' => 'register',
        'error' => $e->getMessage()
    ]);
    
    $statusCode = 500;
    if ($e->getMessage() === 'Missing required fields') {
        $statusCode = 400;
    } else if ($e->getMessage() === 'Method not allowed') {
        $statusCode = 405;
    } else if ($e->getMessage() === 'Test not available for registration') {
        $statusCode = 404;
    }
    
    echo ApiResponse::error($e->getMessage(), $statusCode);
}