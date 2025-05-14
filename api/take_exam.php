<?php
header('Content-Type: application/json');
session_start();
require_once '../db.php';
require_once '../middleware/auth.php';

// Verify JWT token
$token_data = verifyToken();

// if (!isset($_SESSION['student_id'])) {
//     http_response_code(401);
//     echo json_encode(['error' => 'Unauthorized']);
//     exit();
// }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$conn = Database::getInstance()->getConnection();

$class = mysqli_real_escape_string($conn, $_SESSION['student_class']);
$subject = mysqli_real_escape_string($conn, $_SESSION['student_subject']);
$test_title = mysqli_real_escape_string($conn, $_SESSION['test_title']);

$test_query = "SELECT id FROM tests WHERE title = '$test_title' AND class = '$class' AND subject = '$subject'";
$test_result = mysqli_query($conn, $test_query);
$test = mysqli_fetch_assoc($test_result);

if (!$test) {
    http_response_code(404);
    echo json_encode(['error' => 'No test available']);
    exit();
}

$test_id = $test['id'];
$_SESSION['current_test_id'] = $test_id;

$sql = "SELECT * FROM questions WHERE test_id = $test_id ORDER BY RAND()";
$result = mysqli_query($conn, $sql);
$questions = mysqli_fetch_all($result, MYSQLI_ASSOC);

if (empty($questions)) {
    http_response_code(404);
    echo json_encode(['error' => 'No questions available']);
    exit();
}

$_SESSION['exam_questions'] = $questions;
echo json_encode(['success' => true, 'questions' => $questions]);