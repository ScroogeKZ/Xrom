<?php

namespace App\Models;

use PDO;
use PDOException;
use Exception;

require_once __DIR__ . '/../../config/database.php';

class DashboardWidget {
    private $db;
    
    public function __construct() {
        $this->db = \Database::getInstance()->getConnection();
    }
    
    public function getUserWidgets($userId) {
        $stmt = $this->db->prepare("
            SELECT * FROM dashboard_widgets 
            WHERE user_id = ? AND is_enabled = true
            ORDER BY position_y, position_x ASC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function saveUserWidgets($userId, $widgets) {
        try {
            $this->db->beginTransaction();
            
            // Remove existing widgets for user
            $stmt = $this->db->prepare("DELETE FROM dashboard_widgets WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Insert new widgets
            $stmt = $this->db->prepare("
                INSERT INTO dashboard_widgets (user_id, widget_type, widget_config, position_x, position_y, is_enabled) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($widgets as $index => $widget) {
                $stmt->execute([
                    $userId,
                    $widget['type'],
                    json_encode($widget['config'] ?? []),
                    $widget['x'] ?? 0,
                    $widget['y'] ?? $index,
                    $widget['enabled'] ?? true
                ]);
            }
            
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollback();
            error_log("Error saving user widgets: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAvailableWidgets() {
        return [
            'orders_stats' => [
                'name' => 'Статистика заказов',
                'description' => 'Общее количество заказов и статусы',
                'size' => 'medium'
            ],
            'revenue_chart' => [
                'name' => 'График доходов',
                'description' => 'Доходы за последние 7 дней',
                'size' => 'large'
            ],
            'recent_orders' => [
                'name' => 'Последние заказы',
                'description' => 'Список последних заказов',
                'size' => 'large'
            ],
            'carriers_stats' => [
                'name' => 'Статистика перевозчиков',
                'description' => 'Активные перевозчики и транспорт',
                'size' => 'medium'
            ],
            'quick_actions' => [
                'name' => 'Быстрые действия',
                'description' => 'Часто используемые функции',
                'size' => 'small'
            ],
            'system_status' => [
                'name' => 'Состояние системы',
                'description' => 'Статус системы и уведомления',
                'size' => 'medium'
            ]
        ];
    }
    
    public function getWidgetData($widgetType, $config = []) {
        switch ($widgetType) {
            case 'orders_stats':
                return $this->getOrdersStats();
            case 'revenue_chart':
                return $this->getRevenueChart();
            case 'recent_orders':
                return $this->getRecentOrders($config['limit'] ?? 5);
            case 'carriers_stats':
                return $this->getCarriersStats();
            case 'quick_actions':
                return $this->getQuickActions();
            case 'system_status':
                return $this->getSystemStatus();
            default:
                return [];
        }
    }
    
    private function getOrdersStats() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'new' THEN 1 END) as new_orders,
                COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                COUNT(CASE WHEN created_at >= CURRENT_DATE THEN 1 END) as today_orders
            FROM shipment_orders
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getRevenueChart() {
        $stmt = $this->db->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as orders_count,
                COALESCE(SUM(shipping_cost), 0) as revenue
            FROM shipment_orders 
            WHERE created_at >= CURRENT_DATE - INTERVAL '7 days'
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getRecentOrders($limit = 5) {
        $stmt = $this->db->prepare("
            SELECT 
                id,
                order_type,
                status,
                contact_name,
                pickup_address,
                delivery_address,
                created_at
            FROM shipment_orders 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getCarriersStats() {
        $carriersStmt = $this->db->prepare("SELECT COUNT(*) as total FROM carriers WHERE is_active = true");
        $carriersStmt->execute();
        $carriers = $carriersStmt->fetch(PDO::FETCH_ASSOC);
        
        $vehiclesStmt = $this->db->prepare("SELECT COUNT(*) as total FROM vehicles WHERE is_active = true");
        $vehiclesStmt->execute();
        $vehicles = $vehiclesStmt->fetch(PDO::FETCH_ASSOC);
        
        $driversStmt = $this->db->prepare("SELECT COUNT(*) as total FROM drivers WHERE is_active = true");
        $driversStmt->execute();
        $drivers = $driversStmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'active_carriers' => $carriers['total'],
            'available_vehicles' => $vehicles['total'],
            'available_drivers' => $drivers['total']
        ];
    }
    
    private function getQuickActions() {
        return [
            ['name' => 'Новый заказ', 'url' => '/astana.php', 'icon' => 'plus'],
            ['name' => 'Региональный заказ', 'url' => '/regional.php', 'icon' => 'truck'],
            ['name' => 'Отчеты', 'url' => '/admin/reports.php', 'icon' => 'chart-bar'],
            ['name' => 'Настройки', 'url' => '/admin/settings.php', 'icon' => 'cog']
        ];
    }
    
    private function getSystemStatus() {
        $uptime = sys_getloadavg();
        return [
            'status' => 'operational',
            'load_average' => $uptime[0] ?? 0,
            'php_version' => phpversion(),
            'database_status' => 'connected'
        ];
    }
}