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
    $endpoint = 'update_question';

    // Check rate limit
    if (!$rateLimiter->checkLimit($clientIP, $endpoint)) {
        Logger::info('Rate limit exceeded', ['ip' => $clientIP, 'endpoint' => $endpoint]);
        echo ApiResponse::error('Rate limit exceeded. Please try again later.', 429);
        exit();
    }

    // Verify JWT token and require admin role
    $token_data = verifyToken('admin');
    $conn = Database::getInstance()->getConnection();

    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        throw new Exception('Method not allowed');
    }

    // Get PUT data
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    if (!isset($data['question_id']) || !isset($data['question']) || 
        !isset($data['option1']) || !isset($data['option2']) || 
        !isset($data['option3']) || !isset($data['option4']) || 
        !isset($data['correct_answer'])) {
        throw new Exception('Missing required fields');
    }

    // Sanitize inputs
    $question_id = intval($data['question_id']);
    $question = mysqli_real_escape_string($conn, $data['question']);
    $option1 = mysqli_real_escape_string($conn, $data['option1']);
    $option2 = mysqli_real_escape_string($conn, $data['option2']);
    $option3 = mysqli_real_escape_string($conn, $data['option3']);
    $option4 = mysqli_real_escape_string($conn, $data['option4']);
    $correct_answer = mysqli_real_escape_string($conn, $data['correct_answer']);

    // Check if question exists
    $check_query = "SELECT id FROM questions WHERE id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $question_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        Logger::info('Question not found', ['question_id' => $question_id]);
        throw new Exception('Question not found');
    }

    // Update question
    $update_sql = "UPDATE questions SET 
                   question_text = ?, 
                   option1 = ?, 
                   option2 = ?, 
                   option3 = ?, 
                   option4 = ?, 
                   correct_answer = ? 
                   WHERE id = ?";

    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssssssi", 
        $question,
        $option1,
        $option2,
        $option3,
        $option4,
        $correct_answer,
        $question_id
    );

    if (!$stmt->execute()) {
        throw new Exception('Error updating question');
    }

    Logger::info('Question updated successfully', [
        'question_id' => $question_id
    ]);

    echo ApiResponse::success([
        'question_id' => $question_id
    ], 'Question updated successfully');

} catch (Exception $e) {
    Logger::error('Operation failed', [
        'endpoint' => 'update_question',
        'error' => $e->getMessage()
    ]);
    
    $statusCode = 500;
    if ($e->getMessage() === 'Question not found') {
        $statusCode = 404;
    } else if ($e->getMessage() === 'Method not allowed') {
        $statusCode = 405;
    } else if ($e->getMessage() === 'Missing required fields') {
        $statusCode = 400;
    }
    
    echo ApiResponse::error($e->getMessage(), $statusCode);
}