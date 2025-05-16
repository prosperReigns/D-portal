<?php
require_once 'Logger.php';
require_once '../vendor/autoload.php';
use Firebase\JWT\JWT;

class TokenManager {
    private static $accessTokenSecret = "your_access_token_secret"; // Change this in production
    private static $refreshTokenSecret = "your_refresh_token_secret"; // Change this in production
    private static $accessTokenExpiry = 900; // 15 minutes
    private static $refreshTokenExpiry = 2592000; // 30 days

    public static function generateTokens($userData) {
        try {
            $accessToken = self::createAccessToken($userData);
            $refreshToken = self::createRefreshToken($userData);

            return [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_in' => self::$accessTokenExpiry
            ];
        } catch (Exception $e) {
            Logger::error('Token generation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private static function createAccessToken($userData) {
        $payload = [
            'user_id' => $userData['id'],
            'role' => $userData['role'],
            'exp' => time() + self::$accessTokenExpiry,
            'iat' => time(),
            'token_type' => 'access'
        ];

        return JWT::encode($payload, self::$accessTokenSecret, 'HS256');
    }

    private static function createRefreshToken($userData) {
        $payload = [
            'user_id' => $userData['id'],
            'role' => $userData['role'],
            'exp' => time() + self::$refreshTokenExpiry,
            'iat' => time(),
            'token_type' => 'refresh'
        ];

        return JWT::encode($payload, self::$refreshTokenSecret, 'HS256');
    }

    public static function refreshAccessToken($refreshToken) {
        try {
            $decoded = JWT::decode($refreshToken, new \Firebase\JWT\Key(self::$refreshTokenSecret, 'HS256'));
            
            if ($decoded->token_type !== 'refresh') {
                throw new Exception('Invalid token type');
            }

            $userData = [
                'id' => $decoded->user_id,
                'role' => $decoded->role
            ];

            return self::createAccessToken($userData);
        } catch (Exception $e) {
            Logger::error('Token refresh failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}