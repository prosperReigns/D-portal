<?php
require_once 'Logger.php';

class ErrorHandler {
    public static function handleException($exception) {
        $errorDetails = [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];
        
        Logger::error('Uncaught Exception', $errorDetails);
        
        http_response_code(500);
        echo json_encode([
            'error' => 'Internal Server Error',
            'message' => $exception->getMessage()
        ]);
    }
    
    public static function handleError($errno, $errstr, $errfile, $errline) {
        $errorDetails = [
            'type' => $errno,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline
        ];
        
        Logger::error('PHP Error', $errorDetails);
        
        return true;
    }
}

// Set error and exception handlers
set_error_handler([ErrorHandler::class, 'handleError']);
set_exception_handler([ErrorHandler::class, 'handleException']);