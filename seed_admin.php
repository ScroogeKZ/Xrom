<?php

require_once 'vendor/autoload.php';
require_once 'src/Models/User.php';

use App\Models\User;

try {
    $userModel = new User();
    
    // Check if admin user already exists
    $existingAdmin = $userModel->findByUsername('admin');
    
    if (!$existingAdmin) {
        // Create admin user
        $admin = $userModel->create('admin', 'admin123');
        echo "Admin user created successfully!\n";
        echo "Username: admin\n";
        echo "Password: admin123\n";
    } else {
        echo "Admin user already exists.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}