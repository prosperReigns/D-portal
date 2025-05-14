<?php
header('Content-Type: application/json');
session_start();
require_once '../db.php';
require_once '../middleware/auth.php';

// Verify JWT token
$token_data = verifyToken();

// if(!isset($_SESSION['admin_id'])) {
//     http_response_code(401);
//     echo json_encode(['error' => 'Unauthorized']);
//     exit();
// }

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$conn = Database::getInstance()->getConnection();
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['question_id'], $data['question_text'], $data['option1'], 
          $data['option2'], $data['option3'], $data['option4'], $data['correct_answer'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

$question_id = mysqli_real_escape_string($conn, $data['question_id']);
$question_text = mysqli_real_escape_string($conn, $data['question_text']);
$option1 = mysqli_real_escape_string($conn, $data['option1']);
$option2 = mysqli_real_escape_string($conn, $data['option2']);
$option3 = mysqli_real_escape_string($conn, $data['option3']);
$option4 = mysqli_real_escape_string($conn, $data['option4']);
$correct_answer = mysqli_real_escape_string($conn, $data['correct_answer']);

$sql = "UPDATE questions SET 
        question_text = '$question_text',
        option1 = '$option1',
        option2 = '$option2',
        option3 = '$option3',
        option4 = '$option4',
        correct_answer = '$correct_answer'
        WHERE id = $question_id";

if (mysqli_query($conn, $sql)) {
    echo json_encode(['success' => true, 'message' => 'Question updated successfully']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error updating question']);
}