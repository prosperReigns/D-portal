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

if (!isset($data['name'], $data['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing credentials']);
    exit();
}

$username = mysqli_real_escape_string($conn, $data['name']);
$password = mysqli_real_escape_string($conn, $data['password']);

$sql = "SELECT * FROM admins WHERE username = '$username'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 1) {
    $row = mysqli_fetch_assoc($result);
    if ($password === $row['password']) {
        // JWT Configuration
        $secret_key = "your_secret_key_here"; // Change this to a secure secret key
        $issued_at = time();
        $expiration_time = $issued_at + (60 * 60); // Token valid for 1 hour

        // Create token payload
        $payload = array(
            "iat" => $issued_at,
            "exp" => $expiration_time,
            "admin_id" => $row['id'],
            "username" => $row['username']
        );

        // Generate JWT token
        $token = JWT::encode($payload, $secret_key, 'HS256');

        echo json_encode([
            'success' => true, 
            'message' => 'Login successful',
            'token' => $token
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid password']);
    }
} else {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid username']);
}