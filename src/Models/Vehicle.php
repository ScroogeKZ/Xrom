<?php

namespace App\Models;

use PDO;

class Vehicle {
    private $db;
    
    public function __construct() {
        $this->db = \Database::getInstance()->getConnection();
    }
    
    public function getAll($status = null, $carrierId = null) {
        $sql = "SELECT v.*, c.name as carrier_name 
                FROM vehicles v 
                LEFT JOIN carriers c ON v.carrier_id = c.id 
                WHERE 1=1";
        $params = [];
        
        if ($status) {
            $sql .= " AND v.status = ?";
            $params[] = $status;
        }
        
        if ($carrierId) {
            $sql .= " AND v.carrier_id = ?";
            $params[] = $carrierId;
        }
        
        $sql .= " ORDER BY v.license_plate";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT v.*, c.name as carrier_name 
            FROM vehicles v 
            LEFT JOIN carriers c ON v.carrier_id = c.id 
            WHERE v.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO vehicles (carrier_id, license_plate, vehicle_type, make, model, 
                                capacity_weight, capacity_volume, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['carrier_id'],
            $data['license_plate'],
            $data['vehicle_type'],
            $data['make'],
            $data['model'],
            $data['capacity_weight'],
            $data['capacity_volume'],
            $data['status'] ?? 'available'
        ]);
    }
    
    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE vehicles 
            SET carrier_id = ?, license_plate = ?, vehicle_type = ?, make = ?, model = ?,
                capacity_weight = ?, capacity_volume = ?, status = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['carrier_id'],
            $data['license_plate'],
            $data['vehicle_type'],
            $data['make'],
            $data['model'],
            $data['capacity_weight'],
            $data['capacity_volume'],
            $data['status'],
            $id
        ]);
    }
    
    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE vehicles SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }
    
    public function getAvailableVehicles($carrierId = null) {
        return $this->getAll('available', $carrierId);
    }
    
    public function getVehicleTypes() {
        return [
            'Легковой' => 'Легковой автомобиль',
            'Фургон' => 'Фургон',
            'Грузовик' => 'Грузовой автомобиль',
            'Микроавтобус' => 'Микроавтобус',
            'Пикап' => 'Пикап',
            'Рефрижератор' => 'Рефрижератор',
            'Контейнеровоз' => 'Контейнеровоз'
        ];
    }
    
    public function assignToOrder($vehicleId, $orderId) {
        $vehicle = $this->getById($vehicleId);
        if (!$vehicle) return false;
        
        $stmt = $this->db->prepare("
            UPDATE shipment_orders 
            SET vehicle_id = ?, carrier_id = ?, status_updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $result = $stmt->execute([
            $vehicleId, 
            $vehicle['carrier_id'], 
            $orderId
        ]);
        
        if ($result) {
            $this->updateStatus($vehicleId, 'busy');
        }
        
        return $result;
    }
    
    public function unassignFromOrder($orderId) {
        // Получаем транспорт который был назначен
        $stmt = $this->db->prepare("SELECT vehicle_id FROM shipment_orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order && $order['vehicle_id']) {
            // Освобождаем транспорт
            $this->updateStatus($order['vehicle_id'], 'available');
            
            // Убираем назначение из заказа
            $stmt = $this->db->prepare("
                UPDATE shipment_orders 
                SET vehicle_id = NULL, carrier_id = NULL, status_updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            return $stmt->execute([$orderId]);
        }
        
        return true;
    }
}