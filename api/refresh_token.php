<?php
header('Content-Type: application/json');
require_once '../db.php';
require_once '../middleware/cors.php';
require_once '../middleware/rate_limit.php';
require_once '../utils/Logger.php';
require_once '../utils/ErrorHandler.php';
require_once '../utils/ApiResponse.php';
require_once '../utils/TokenManager.php';

try {
    // Initialize rate limiter
    $rateLimiter = new RateLimit(Database::getInstance()->getConnection());
    $clientIP = $_SERVER['REMOTE_ADDR'];
    $endpoint = 'refresh_token';

    // Check rate limit
    if (!$rateLimiter->checkLimit($clientIP, $endpoint)) {
        Logger::info('Rate limit exceeded', ['ip' => $clientIP, 'endpoint' => $endpoint]);
        echo ApiResponse::error('Rate limit exceeded. Please try again later.', 429);
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['refresh_token'])) {
        throw new Exception('Refresh token is required');
    }

    $refreshToken = $data['refresh_token'];
    $newAccessToken = TokenManager::refreshAccessToken($refreshToken);

    echo ApiResponse::success([
        'access_token' => $newAccessToken,
        'expires_in' => 900 // 15 minutes
    ], 'Access token refreshed successfully');

} catch (Exception $e) {
    Logger::error('Token refresh error', ['error' => $e->getMessage()]);
    echo ApiResponse::error($e->getMessage(), 401);
}