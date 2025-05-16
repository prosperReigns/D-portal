<?php
require_once '../vendor/autoload.php';
require_once '../utils/Logger.php';
require_once '../utils/ApiResponse.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

function verifyToken($requiredRole = null) {
    try {
        $headers = getallheaders();
        
        if (!isset($headers['Authorization'])) {
            Logger::error('Authentication failed', ['reason' => 'No token provided']);
            echo ApiResponse::error('Authentication required', 401);
            exit();
        }

        $authHeader = $headers['Authorization'];
        if (!preg_match('/^Bearer\s+(.+)$/', $authHeader, $matches)) {
            Logger::error('Authentication failed', ['reason' => 'Invalid token format']);
            echo ApiResponse::error('Invalid token format', 401);
            exit();
        }

        $token = $matches[1];
        $secret_key = "your_secret_key_here"; // Consider moving this to environment variable
        
        try {
            $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));
            
            // Check token expiration
            if (isset($decoded->exp) && time() > $decoded->exp) {
                Logger::error('Authentication failed', ['reason' => 'Token expired']);
                echo ApiResponse::error('Token has expired', 401);
                exit();
            }
            
            // Check role if required
            if ($requiredRole !== null) {
                if (!isset($decoded->role) || $decoded->role !== $requiredRole) {
                    Logger::error('Authorization failed', [
                        'required_role' => $requiredRole,
                        'provided_role' => $decoded->role ?? 'none'
                    ]);
                    echo ApiResponse::error('Insufficient permissions', 403);
                    exit();
                }
            }
            
            Logger::info('Authentication successful', [
                'user_id' => $decoded->admin_id ?? $decoded->student_id ?? null,
                'role' => $decoded->role
            ]);
            
            return $decoded;
            
        } catch (ExpiredException $e) {
            Logger::error('Authentication failed', ['reason' => 'Token expired', 'error' => $e->getMessage()]);
            echo ApiResponse::error('Token has expired', 401);
            exit();
        } catch (SignatureInvalidException $e) {
            Logger::error('Authentication failed', ['reason' => 'Invalid signature', 'error' => $e->getMessage()]);
            echo ApiResponse::error('Invalid token', 401);
            exit();
        } catch (Exception $e) {
            Logger::error('Authentication failed', ['reason' => 'Token validation error', 'error' => $e->getMessage()]);
            echo ApiResponse::error('Invalid token', 401);
            exit();
        }
    } catch (Exception $e) {
        Logger::error('Authentication system error', ['error' => $e->getMessage()]);
        echo ApiResponse::error('Authentication system error', 500);
        exit();
    }
}