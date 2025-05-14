<?php
header('Content-Type: application/json');
require_once '../db.php';
require_once '../vendor/autoload.php';
use Firebase\JWT\JWT;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$conn = Database::getInstance()->getConnection();
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['name'], $data['class'], $data['subject'], $data['test_title'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

$name = mysqli_real_escape_string($conn, $data['name']);
$class = mysqli_real_escape_string($conn, $data['class']);
$subject = mysqli_real_escape_string($conn, $data['subject']);
$test_title = mysqli_real_escape_string($conn, $data['test_title']);

$sql = "INSERT INTO students (name, class) VALUES ('$name', '$class')";
if (mysqli_query($conn, $sql)) {
    $student_id = mysqli_insert_id($conn);
    
    // JWT Configuration
    $secret_key = "your_secret_key_here"; // Change this to a secure secret key
    $issued_at = time();
    $expiration_time = $issued_at + (60 * 60 * 24); // Token valid for 24 hours

    // Create token payload
    $payload = array(
        "iat" => $issued_at,
        "exp" => $expiration_time,
        "student_id" => $student_id,
        "name" => $name,
        "class" => $class,
        "subject" => $subject,
        "test_title" => $test_title
    );

    // Generate JWT token
    $token = JWT::encode($payload, $secret_key, 'HS256');
    
    echo json_encode([
        'success' => true, 
        'message' => 'Registration successful',
        'token' => $token,
        'student' => [
            'id' => $student_id,
            'name' => $name,
            'class' => $class,
            'subject' => $subject,
            'test_title' => $test_title
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Registration failed']);
}