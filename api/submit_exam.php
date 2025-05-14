<?php
header('Content-Type: application/json');
session_start();
require_once '../db.php';
require_once '../middleware/auth.php';

// Verify JWT token
$token_data = verifyToken();

if (!isset($_SESSION['student_id']) || !isset($_SESSION['exam_questions']) || !isset($_SESSION['current_test_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized or invalid exam session']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$conn = Database::getInstance()->getConnection();
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['answers']) || !is_array($data['answers'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid answers format']);
    exit();
}

$student_id = $_SESSION['student_id'];
$test_id = $_SESSION['current_test_id'];
$questions = $_SESSION['exam_questions'];
$submitted_answers = $data['answers'];

$score = 0;
$total_questions = count($questions);

foreach ($questions as $question) {
    $question_id = $question['id'];
    if (isset($submitted_answers[$question_id]) && 
        $submitted_answers[$question_id] == $question['correct_answer']) {
        $score++;
    }
}

$sql = "INSERT INTO results (user_id, test_id, score, total_questions) 
        VALUES ($student_id, $test_id, $score, $total_questions)";

if (mysqli_query($conn, $sql)) {
    unset($_SESSION['exam_questions']);
    unset($_SESSION['current_test_id']);
    
    echo json_encode([
        'success' => true,
        'score' => $score,
        'total_questions' => $total_questions,
        'percentage' => ($score / $total_questions) * 100
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error saving exam results']);
}