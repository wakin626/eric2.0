<?php
require_once __DIR__ . '/core/BaseModel.php';

$password = password_hash('admin', PASSWORD_DEFAULT);

$sql = "INSERT INTO users (username, email, password, full_name, department, status) 
        VALUES ('admin', 'admin@system.com', '$password', 'System Admin', 'admin', 1)";

try {
    $pdo = \App\Core\BaseModel::getConnection();
    $pdo->exec($sql);
    echo "Admin user created successfully!\n";
    echo "Username: admin\n";
    echo "Password: admin\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}