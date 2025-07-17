<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\ShipmentOrder;
use App\Auth;

header('Content-Type: application/json');

Auth::requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

$orderModel = new ShipmentOrder();

try {
    switch ($action) {
        case 'get_orders':
            $filters = [];
            if (isset($_GET['order_type'])) $filters['order_type'] = $_GET['order_type'];
            if (isset($_GET['status'])) $filters['status'] = $_GET['status'];
            if (isset($_GET['search'])) $filters['search'] = $_GET['search'];
            
            $page = (int)($_GET['page'] ?? 1);
            $limit = 20;
            $offset = ($page - 1) * $limit;
            
            $filters['limit'] = $limit;
            $filters['offset'] = $offset;
            
            $orders = $orderModel->getAll($filters);
            $total = $orderModel->getCount($filters);
            
            echo json_encode([
                'success' => true,
                'orders' => $orders,
                'total' => $total,
                'page' => $page,
                'pages' => ceil($total / $limit)
            ]);
            break;
            
        case 'update_status':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $orderId = (int)$input['order_id'];
            $newStatus = $input['status'];
            
            $updatedOrder = $orderModel->updateStatus($orderId, $newStatus);
            
            echo json_encode([
                'success' => true,
                'order' => $updatedOrder
            ]);
            break;
            
        case 'get_order':
            $orderId = (int)($_GET['id'] ?? 0);
            $order = $orderModel->getById($orderId);
            
            if (!$order) {
                throw new Exception('Order not found');
            }
            
            echo json_encode([
                'success' => true,
                'order' => $order
            ]);
            break;
            
        case 'delete_order':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $orderId = (int)$input['order_id'];
            
            // We'll implement soft delete by setting status to 'deleted'
            $deletedOrder = $orderModel->updateStatus($orderId, 'deleted');
            
            echo json_encode([
                'success' => true,
                'order' => $deletedOrder
            ]);
            break;
            
        case 'stats':
            $stats = [
                'total_orders' => $orderModel->getCount(),
                'astana_orders' => $orderModel->getCount(['order_type' => 'astana']),
                'regional_orders' => $orderModel->getCount(['order_type' => 'regional']),
                'new_orders' => $orderModel->getCount(['status' => 'new']),
                'processing_orders' => $orderModel->getCount(['status' => 'processing']),
                'completed_orders' => $orderModel->getCount(['status' => 'completed'])
            ];
            
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}