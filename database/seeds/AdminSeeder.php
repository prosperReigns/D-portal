<?php
require_once '../../db.php';
require_once '../../utils/Logger.php';

try {
    $conn = Database::getInstance()->getConnection();
    
    // Check if admins table exists, if not create it
    $createTable = "CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(50) NOT NULL DEFAULT 'admin',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (!$conn->query($createTable)) {
        throw new Exception("Error creating admins table: " . $conn->error);
    }

    // Default admin credentials
    $defaultAdmin = [
        'username' => 'admin',
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'role' => 'admin'
    ];

    // Check if admin already exists
    $checkAdmin = "SELECT id FROM admins WHERE username = ?";
    $stmt = $conn->prepare($checkAdmin);
    $stmt->bind_param("s", $defaultAdmin['username']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Insert default admin if not exists
        $insertAdmin = "INSERT INTO admins (username, password, role) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insertAdmin);
        $stmt->bind_param("sss", $defaultAdmin['username'], $defaultAdmin['password'], $defaultAdmin['role']);
        
        if ($stmt->execute()) {
            echo "Default admin user created successfully!\n";
            echo "Username: admin\n";
            echo "Password: admin123\n";
            echo "Role: admin\n";
            Logger::info('Default admin created', ['username' => $defaultAdmin['username'], 'role' => $defaultAdmin['role']]);
        } else {
            throw new Exception("Error creating default admin: " . $stmt->error);
        }
    } else {
        echo "Admin user already exists.\n";
    }

} catch (Exception $e) {
    Logger::error('Admin seeder failed', ['error' => $e->getMessage()]);
    echo "Error: " . $e->getMessage() . "\n";
}