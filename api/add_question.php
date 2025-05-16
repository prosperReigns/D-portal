<?php
header('Content-Type: application/json');
session_start();
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
    $endpoint = 'add_question';

    // Check rate limit
    if (!$rateLimiter->checkLimit($clientIP, $endpoint)) {
        Logger::info('Rate limit exceeded', ['ip' => $clientIP, 'endpoint' => $endpoint]);
        echo ApiResponse::error('Rate limit exceeded. Please try again later.', 429);
        exit();
    }

    // Verify JWT token and require admin role
    $token_data = verifyToken('admin');
    $conn = Database::getInstance()->getConnection();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    if (!isset($data['test_title'], $data['class'], $data['subject'], $data['question'], 
              $data['option1'], $data['option2'], $data['option3'], $data['option4'], $data['correct_answer'])) {
        throw new Exception('Missing required fields');
    }

    // Create or get test
    $test_title = mysqli_real_escape_string($conn, $data['test_title']);
    $class = mysqli_real_escape_string($conn, $data['class']);
    $subject = mysqli_real_escape_string($conn, $data['subject']);

    // Check if test exists
    $check_sql = "SELECT id FROM tests WHERE title = '$test_title' AND class = '$class' AND subject = '$subject'";
    $check_result = $conn->query($check_sql);

    if (mysqli_num_rows($check_result) > 0) {
        $test = mysqli_fetch_assoc($check_result);
        $test_id = $test['id'];
        Logger::info('Using existing test', ['test_id' => $test_id]);
    } else {
        // Create new test
        $test_sql = "INSERT INTO tests (title, class, subject) VALUES ('$test_title', '$class', '$subject')";
        if (!$conn->query($test_sql)) {
            throw new Exception('Error creating test');
        }
        $test_id = mysqli_insert_id($conn);
        Logger::info('Created new test', ['test_id' => $test_id]);
    }

    // Add question
    $question = mysqli_real_escape_string($conn, $data['question']);
    $option1 = mysqli_real_escape_string($conn, $data['option1']);
    $option2 = mysqli_real_escape_string($conn, $data['option2']);
    $option3 = mysqli_real_escape_string($conn, $data['option3']);
    $option4 = mysqli_real_escape_string($conn, $data['option4']);
    $correct_answer = mysqli_real_escape_string($conn, $data['correct_answer']);

    $sql = "INSERT INTO questions (question_text, option1, option2, option3, option4, correct_answer, test_id, class, subject) 
            VALUES ('$question', '$option1', '$option2', '$option3', '$option4', '$correct_answer', $test_id, '$class', '$subject')";

    if (!$conn->query($sql)) {
        throw new Exception('Error adding question');
    }

    Logger::info('Question added successfully', [
        'test_id' => $test_id,
        'class' => $class,
        'subject' => $subject
    ]);

    echo ApiResponse::success([
        'test_id' => $test_id,
        'test_details' => [
            'title' => $test_title,
            'class' => $class,
            'subject' => $subject
        ]
    ], 'Question added successfully');

} catch (Exception $e) {
    Logger::error('Operation failed', [
        'endpoint' => 'add_question',
        'error' => $e->getMessage()
    ]);
    echo ApiResponse::error($e->getMessage(), 500);
}