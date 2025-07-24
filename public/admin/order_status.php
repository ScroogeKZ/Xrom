<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;
use App\Models\ShipmentOrder;
use App\Models\Driver;

// Проверка авторизации
if (!Auth::isAuthenticated()) {
    header('Location: /admin/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/orders.php');
    exit;
}

$orderId = $_POST['order_id'] ?? null;
$newStatus = $_POST['status'] ?? null;
$driverId = $_POST['driver_id'] ?? null;

if (!$orderId || !$newStatus) {
    $_SESSION['error'] = 'Не указаны необходимые параметры';
    header('Location: /admin/orders.php');
    exit;
}

try {
    $shipmentOrder = new ShipmentOrder();
    $driver = new Driver();
    
    // Если назначается водитель
    if ($driverId && $driverId !== 'unassign') {
        $result = $shipmentOrder->updateStatus($orderId, $newStatus, $driverId);
        if ($result) {
            $driver->updateStatus($driverId, 'busy');
        }
    } elseif ($driverId === 'unassign') {
        // Снятие назначения водителя
        $shipmentOrder->unassignDriver($orderId);
        $result = $shipmentOrder->updateStatus($orderId, $newStatus);
    } else {
        // Просто обновление статуса
        $result = $shipmentOrder->updateStatus($orderId, $newStatus);
    }
    
    if ($result) {
        $_SESSION['success'] = 'Статус заказа успешно обновлен';
    } else {
        $_SESSION['error'] = 'Ошибка при обновлении статуса';
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Ошибка: ' . $e->getMessage();
}

header('Location: /admin/orders.php');
exit;
?>