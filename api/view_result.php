<?php
header('Content-Type: application/json');
session_start();
require_once '../db.php';
require_once '../middleware/auth.php';

// Verify JWT token
$token_data = verifyToken();

// if(!isset($_SESSION['admin_id']) && !isset($_SESSION['student_id'])) {
//     http_response_code(401);
//     echo json_encode(['error' => 'Unauthorized']);
//     exit();
// }

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$conn = Database::getInstance()->getConnection();

$sql = "SELECT r.*, s.name AS student_name, s.class AS student_class, t.subject, t.title AS test_title 
        FROM results r
        JOIN students s ON r.user_id = s.id
        JOIN tests t ON r.test_id = t.id";

// If student is viewing their own results
if (isset($_SESSION['student_id'])) {
    $sql .= " WHERE r.user_id = " . $_SESSION['student_id'];
}

$result = mysqli_query($conn, $sql);
$results = mysqli_fetch_all($result, MYSQLI_ASSOC);

echo json_encode(['success' => true, 'results' => $results]);