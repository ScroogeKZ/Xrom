<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/database.php';

use App\Models\ShipmentOrder;
use App\ClientAuth;

// CORS headers для интеграции с внешними системами
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Проверяем авторизацию клиента
    if (!ClientAuth::isLoggedIn()) {
        throw new Exception('Unauthorized', 401);
    }

    $method = $_SERVER['REQUEST_METHOD'];
    $clientPhone = ClientAuth::getClientPhone();
    $shipmentOrder = new ShipmentOrder();

    switch ($method) {
        case 'GET':
            // Получить заказы клиента с CRM данными
            $orders = $shipmentOrder->getByClientPhone($clientPhone);
            
            // Форматируем данные для API
            $formattedOrders = array_map(function($order) {
                return [
                    'id' => $order['id'],
                    'status' => $order['status'],
                    'status_name' => ShipmentOrder::getStatusName($order['status']),
                    'created_at' => $order['created_at'],
                    'pickup_address' => $order['pickup_address'],
                    'delivery_address' => $order['delivery_address'],
                    'cargo_type' => $order['cargo_type'],
                    'weight' => $order['weight'],
                    
                    // CRM интеграция
                    'carrier' => $order['carrier_name'] ? [
                        'name' => $order['carrier_name'],
                        'phone' => $order['carrier_phone'],
                        'rating' => $order['rating'],
                        'license' => $order['carrier_license']
                    ] : null,
                    
                    'driver' => $order['driver_name'] ? [
                        'name' => $order['driver_name'],
                        'phone' => $order['driver_phone'],
                        'license' => $order['driver_license']
                    ] : null,
                    
                    'vehicle' => $order['brand'] ? [
                        'brand' => $order['brand'],
                        'model' => $order['model'],
                        'year' => $order['year'],
                        'license_plate' => $order['license_plate'],
                        'type' => $order['vehicle_type']
                    ] : null,
                    
                    'tracking' => [
                        'last_update' => $order['status_updated_at'],
                        'can_track' => in_array($order['status'], ['assigned', 'picked_up', 'in_transit'])
                    ]
                ];
            }, $orders);

            echo json_encode([
                'success' => true,
                'data' => $formattedOrders,
                'count' => count($formattedOrders)
            ]);
            break;

        case 'PUT':
            // Обновление статуса заказа (только отмена для клиентов)
            $input = json_decode(file_get_contents('php://input'), true);
            $orderId = $input['id'] ?? null;
            $newStatus = $input['status'] ?? null;

            if (!$orderId || !$newStatus) {
                throw new Exception('Missing required fields');
            }

            // Проверяем, что заказ принадлежит клиенту
            $orders = $shipmentOrder->getByClientPhone($clientPhone);
            $canUpdate = false;
            $currentStatus = null;
            
            foreach ($orders as $order) {
                if ($order['id'] == $orderId) {
                    $canUpdate = true;
                    $currentStatus = $order['status'];
                    break;
                }
            }

            if (!$canUpdate) {
                throw new Exception('Order not found or access denied');
            }

            // Клиенты могут только отменять новые заказы
            if ($newStatus === 'cancelled' && $currentStatus === 'new') {
                $updatedOrder = $shipmentOrder->updateStatus($orderId, $newStatus);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Заказ отменен',
                    'data' => $updatedOrder
                ]);
            } else {
                throw new Exception('Operation not allowed');
            }
            break;

        default:
            throw new Exception('Method not allowed');
    }

} catch (Exception $e) {
    $statusCode = $e->getCode() ?: 500;
    http_response_code($statusCode);
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $statusCode
    ]);
}
?>