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
    $endpoint = 'submit_exam';

    // Check rate limit
    if (!$rateLimiter->checkLimit($clientIP, $endpoint)) {
        Logger::info('Rate limit exceeded', ['ip' => $clientIP, 'endpoint' => $endpoint]);
        echo ApiResponse::error('Rate limit exceeded. Please try again later.', 429);
        exit();
    }
    // Verify JWT token and require student role
    $token_data = verifyToken('student');
    $conn = Database::getInstance()->getConnection();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['answers']) || !is_array($data['answers'])) {
        throw new Exception('Invalid answer format');
    }

    // Get student details from token
    $student_id = $token_data->student_id;
    $class = mysqli_real_escape_string($conn, $token_data->class);
    $subject = mysqli_real_escape_string($conn, $token_data->subject);
    $test_title = mysqli_real_escape_string($conn, $token_data->test_title);

    // Get test ID
    $test_query = "SELECT id FROM tests WHERE title = '$test_title' AND class = '$class' AND subject = '$subject'";
    $test_result = $conn->query($test_query);

    if (!$test_result || mysqli_num_rows($test_result) === 0) {
        Logger::info('Test not found', [
            'student_id' => $student_id,
            'test_title' => $test_title
        ]);
        throw new Exception('Test not found');
    }

    $test = mysqli_fetch_assoc($test_result);
    $test_id = $test['id'];

    // Calculate score
    $score = 0;
    $total_questions = count($data['answers']);

    foreach ($data['answers'] as $question_id => $answer) {
        $question_query = "SELECT correct_answer FROM questions WHERE id = " . intval($question_id);
        $question_result = $conn->query($question_query);

        if (!$question_result) {
            Logger::error('Error fetching question', [
                'question_id' => $question_id
            ]);
            continue;
        }

        $question = mysqli_fetch_assoc($question_result);
        if ($question && $question['correct_answer'] === $answer) {
            $score++;
        }
    }

    // Calculate percentage
    $percentage = ($score / $total_questions) * 100;

    // Save result
    $result_sql = "INSERT INTO results (student_id, test_id, score, total_questions, percentage) 
                   VALUES ($student_id, $test_id, $score, $total_questions, $percentage)";

    if (!$conn->query($result_sql)) {
        throw new Exception('Error saving exam results');
    }

    $result_id = mysqli_insert_id($conn);

    Logger::info('Exam submitted successfully', [
        'student_id' => $student_id,
        'test_id' => $test_id,
        'score' => $score,
        'percentage' => $percentage
    ]);

    echo ApiResponse::success([
        'result_id' => $result_id,
        'score' => $score,
        'total_questions' => $total_questions,
        'percentage' => $percentage
    ], 'Exam submitted successfully');

} catch (Exception $e) {
    Logger::error('Operation failed', [
        'endpoint' => 'submit_exam',
        'error' => $e->getMessage()
    ]);
    
    $statusCode = 500;
    if ($e->getMessage() === 'Test not found') {
        $statusCode = 404;
    } else if ($e->getMessage() === 'Method not allowed') {
        $statusCode = 405;
    } else if ($e->getMessage() === 'Invalid answer format') {
        $statusCode = 400;
    }
    
    echo ApiResponse::error($e->getMessage(), $statusCode);
}