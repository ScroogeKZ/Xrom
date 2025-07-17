<?php

require_once 'vendor/autoload.php';
require_once 'src/Models/ShipmentOrder.php';
require_once 'src/Models/User.php';

use App\Models\ShipmentOrder;
use App\Models\User;

echo "Testing database connectivity...\n";

try {
    // Test user model
    $userModel = new User();
    echo "✓ User model initialized successfully\n";
    
    // Test finding admin user
    $admin = $userModel->findByUsername('admin');
    if ($admin) {
        echo "✓ Admin user found in database\n";
    } else {
        echo "✗ Admin user not found\n";
    }
    
    // Test shipment order model
    $orderModel = new ShipmentOrder();
    echo "✓ ShipmentOrder model initialized successfully\n";
    
    // Test getting orders
    $orders = $orderModel->getAll();
    echo "✓ Successfully retrieved " . count($orders) . " orders from database\n";
    
    echo "\nDatabase integration test completed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Database test failed: " . $e->getMessage() . "\n";
}