<?php

namespace App\Models;

use PDO;

class Driver {
    private $db;
    
    public function __construct() {
        $this->db = \Database::getInstance()->getConnection();
    }
    
    public function getAll($status = null, $carrierId = null) {
        $sql = "SELECT d.*, c.name as carrier_name 
                FROM drivers d 
                LEFT JOIN carriers c ON d.carrier_id = c.id 
                WHERE 1=1";
        $params = [];
        
        if ($status) {
            $sql .= " AND d.status = ?";
            $params[] = $status;
        }
        
        if ($carrierId) {
            $sql .= " AND d.carrier_id = ?";
            $params[] = $carrierId;
        }
        
        $sql .= " ORDER BY d.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM drivers WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO drivers (name, phone, license_number, carrier_id, experience_years, status) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['name'],
            $data['phone'],
            $data['license_number'],
            $data['carrier_id'],
            $data['experience_years'] ?? 0,
            $data['status'] ?? 'available'
        ]);
    }
    
    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE drivers 
            SET name = ?, phone = ?, license_number = ?, carrier_id = ?, 
                experience_years = ?, status = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['name'],
            $data['phone'],
            $data['license_number'],
            $data['carrier_id'],
            $data['experience_years'] ?? 0,
            $data['status'],
            $id
        ]);
    }
    
    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE drivers SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }
    
    public function getAvailableDrivers() {
        return $this->getAll('available');
    }
    
    public function assignToOrder($driverId, $orderId) {
        $driver = $this->getById($driverId);
        if (!$driver) return false;
        
        $stmt = $this->db->prepare("
            UPDATE shipment_orders 
            SET driver_id = ?, driver_name = ?, status_updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $result = $stmt->execute([$driverId, $driver['name'], $orderId]);
        
        if ($result) {
            $this->updateStatus($driverId, 'busy');
        }
        
        return $result;
    }
    
    public function unassignFromOrder($orderId) {
        // Получаем водителя который был назначен
        $stmt = $this->db->prepare("SELECT driver_id FROM shipment_orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order && $order['driver_id']) {
            // Освобождаем водителя
            $this->updateStatus($order['driver_id'], 'available');
            
            // Убираем назначение из заказа
            $stmt = $this->db->prepare("
                UPDATE shipment_orders 
                SET driver_id = NULL, driver_name = NULL, status_updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            return $stmt->execute([$orderId]);
        }
        
        return true;
    }
}