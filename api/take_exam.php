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
    $endpoint = 'take_exam';

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

    // Use data from token instead of session
    $class = mysqli_real_escape_string($conn, $token_data->class);
    $subject = mysqli_real_escape_string($conn, $token_data->subject);
    $test_title = mysqli_real_escape_string($conn, $token_data->test_title);

    // Check if test exists
    $test_query = "SELECT id FROM tests WHERE title = '$test_title' AND class = '$class' AND subject = '$subject'";
    $test_result = $conn->query($test_query);

    if (!$test_result || mysqli_num_rows($test_result) === 0) {
        Logger::info('Test not found', [
            'class' => $class,
            'subject' => $subject,
            'test_title' => $test_title
        ]);
        throw new Exception('No test available');
    }

    $test = mysqli_fetch_assoc($test_result);
    $test_id = $test['id'];

    Logger::info('Test found', ['test_id' => $test_id]);

    // Check if student has already taken this test
    $check_attempt_sql = "SELECT id FROM results WHERE student_id = ? AND test_id = ?";
    $stmt = $conn->prepare($check_attempt_sql);
    $student_id = $token_data->student_id;
    $stmt->bind_param("ii", $student_id, $test_id);
    $stmt->execute();
    $attempt_result = $stmt->get_result();

    if ($attempt_result->num_rows > 0) {
        Logger::info('Student already took test', [
            'student_id' => $student_id,
            'test_id' => $test_id
        ]);
        throw new Exception('You have already taken this test');
    }

    // Get questions for the test
    $sql = "SELECT * FROM questions WHERE test_id = $test_id ORDER BY RAND()";
    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception('Error fetching questions');
    }

    $questions = mysqli_fetch_all($result, MYSQLI_ASSOC);

    if (empty($questions)) {
        Logger::info('No questions found for test', ['test_id' => $test_id]);
        throw new Exception('No questions available');
    }

    // Remove correct answers from questions before sending to student
    $filtered_questions = array_map(function($q) {
        unset($q['correct_answer']);
        return $q;
    }, $questions);

    Logger::info('Exam started successfully', [
        'student_id' => $student_id,
        'test_id' => $test_id,
        'question_count' => count($questions)
    ]);

    echo ApiResponse::success([
        'test_id' => $test_id,
        'questions' => $filtered_questions,
        'total_questions' => count($questions)
    ], 'Exam started successfully');

} catch (Exception $e) {
    Logger::error('Operation failed', [
        'endpoint' => 'take_exam',
        'error' => $e->getMessage()
    ]);
    
    $statusCode = 500;
    if ($e->getMessage() === 'No test available' || $e->getMessage() === 'No questions available') {
        $statusCode = 404;
    } else if ($e->getMessage() === 'Method not allowed') {
        $statusCode = 405;
    } else if ($e->getMessage() === 'You have already taken this test') {
        $statusCode = 403;
    }
    
    echo ApiResponse::error($e->getMessage(), $statusCode);
}