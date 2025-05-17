<?php
header('Content-Type: application/json');
require_once '../db.php';
require_once '../middleware/auth.php';
require_once '../middleware/cors.php';
require_once '../middleware/rate_limit.php';
require_once '../utils/Logger.php';
require_once '../utils/ErrorHandler.php';
require_once '../utils/ApiResponse.php';

try {
    // Initialize rate limiter
    $rateLimiter = new RateLimit(Database::getInstance()->getConnection());
    $clientIP = $_SERVER['REMOTE_ADDR'];
    $endpoint = 'view_result';

    // Check rate limit
    if (!$rateLimiter->checkLimit($clientIP, $endpoint)) {
        Logger::info('Rate limit exceeded', ['ip' => $clientIP, 'endpoint' => $endpoint]);
        echo ApiResponse::error('Rate limit exceeded. Please try again later.', 429);
        exit();
    }

    // Verify JWT token
    $token_data = verifyToken();
    $conn = Database::getInstance()->getConnection();

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Method not allowed');
    }

    // Get result ID from query parameters
    $result_id = isset($_GET['result_id']) ? intval($_GET['result_id']) : null;
    $student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : null;

    // Prepare base query
    if ($result_id) {
        $query = "SELECT r.*, s.name as student_name, s.class, t.title as test_title, t.subject 
                  FROM results r 
                  JOIN students s ON r.student_id = s.id 
                  JOIN tests t ON r.test_id = t.id 
                  WHERE r.id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $result_id);
    } else if ($student_id) {
        $query = "SELECT r.*, s.name as student_name, s.class, t.title as test_title, t.subject 
                  FROM results r 
                  JOIN students s ON r.student_id = s.id 
                  JOIN tests t ON r.test_id = t.id 
                  WHERE r.student_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $student_id);
    } else {
        // For admin, fetch all results
        if ($token_data->role !== 'admin') {
            throw new Exception('Unauthorized access');
        }
        $query = "SELECT r.*, s.name as student_name, s.class, t.title as test_title, t.subject 
                  FROM results r 
                  JOIN students s ON r.student_id = s.id 
                  JOIN tests t ON r.test_id = t.id";
        $stmt = $conn->prepare($query);
    }

    // Execute query
    if (!$stmt->execute()) {
        throw new Exception('Error fetching results');
    }

    $result = $stmt->get_result();
    $results = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // Check permissions
    if ($token_data->role === 'student') {
        // Students can only view their own results
        if (!$student_id || $student_id != $token_data->student_id) {
            throw new Exception('Unauthorized access');
        }
    }

    if (empty($results)) {
        Logger::info('No results found', [
            'result_id' => $result_id,
            'student_id' => $student_id
        ]);
        throw new Exception('No results found');
    }

    Logger::info('Results retrieved successfully', [
        'count' => count($results),
        'role' => $token_data->role
    ]);

    echo ApiResponse::success([
        'results' => $results
    ], 'Results retrieved successfully');

} catch (Exception $e) {
    Logger::error('Operation failed', [
        'endpoint' => 'view_result',
        'error' => $e->getMessage()
    ]);
    
    $statusCode = 500;
    if ($e->getMessage() === 'No results found') {
        $statusCode = 404;
    } else if ($e->getMessage() === 'Method not allowed') {
        $statusCode = 405;
    } else if ($e->getMessage() === 'Unauthorized access') {
        $statusCode = 403;
    }
    
    echo ApiResponse::error($e->getMessage(), $statusCode);
}