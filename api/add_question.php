<?php
header('Content-Type: application/json');
session_start();
require_once '../db.php';
require_once '../middleware/auth.php';
require_once '../middleware/cors.php';

header('Content-Type: application/json');
// Verify JWT token
$token_data = verifyToken();

// Check admin authentication
// if(!isset($_SESSION['admin_id'])) {
//     http_response_code(401);
//     echo json_encode(['error' => 'Unauthorized']);
//     exit();
// }

$conn = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Check for all required fields including test details
if (!isset($data['test_title'], $data['class'], $data['subject'], $data['question'], 
          $data['option1'], $data['option2'], $data['option3'], $data['option4'], $data['correct_answer'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields. Required: test_title, class, subject, question, option1-4, correct_answer']);
    exit();
}

// Create or get test
$test_title = mysqli_real_escape_string($conn, $data['test_title']);
$class = mysqli_real_escape_string($conn, $data['class']);
$subject = mysqli_real_escape_string($conn, $data['subject']);

$check_sql = "SELECT id FROM tests WHERE title = '$test_title' AND class = '$class' AND subject = '$subject'";
$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) > 0) {
    $test = mysqli_fetch_assoc($check_result);
    $test_id = $test['id'];
} else {
    $test_sql = "INSERT INTO tests (title, class, subject) VALUES ('$test_title', '$class', '$subject')";
    if (!mysqli_query($conn, $test_sql)) {
        http_response_code(500);
        echo json_encode(['error' => 'Error creating test']);
        exit();
    }
    $test_id = mysqli_insert_id($conn);
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

if (mysqli_query($conn, $sql)) {
    echo json_encode([
        'success' => true, 
        'message' => 'Question added successfully',
        'test_id' => $test_id,
        'test_details' => [
            'title' => $test_title,
            'class' => $class,
            'subject' => $subject
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error adding question']);
}