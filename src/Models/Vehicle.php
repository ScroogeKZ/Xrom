<?php

namespace App\Models;

use PDO;

class Vehicle {
    private $db;
    
    public function __construct() {
        $this->db = \Database::getInstance()->getConnection();
    }
    
    public function getAll($status = null, $carrierId = null) {
        $sql = "SELECT v.*, c.company_name 
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
        
        $sql .= " ORDER BY v.vehicle_number";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT v.*, c.company_name 
            FROM vehicles v 
            LEFT JOIN carriers c ON v.carrier_id = c.id 
            WHERE v.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO vehicles (carrier_id, vehicle_number, vehicle_type, brand, model, year, 
                                capacity_weight, capacity_volume, fuel_type, status, insurance_expires, tech_inspection_expires) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['carrier_id'],
            $data['vehicle_number'],
            $data['vehicle_type'],
            $data['brand'],
            $data['model'],
            $data['year'],
            $data['capacity_weight'],
            $data['capacity_volume'],
            $data['fuel_type'],
            $data['status'] ?? 'available',
            $data['insurance_expires'],
            $data['tech_inspection_expires']
        ]);
    }
    
    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE vehicles 
            SET carrier_id = ?, vehicle_number = ?, vehicle_type = ?, brand = ?, model = ?, year = ?,
                capacity_weight = ?, capacity_volume = ?, fuel_type = ?, status = ?, 
                insurance_expires = ?, tech_inspection_expires = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['carrier_id'],
            $data['vehicle_number'],
            $data['vehicle_type'],
            $data['brand'],
            $data['model'],
            $data['year'],
            $data['capacity_weight'],
            $data['capacity_volume'],
            $data['fuel_type'],
            $data['status'],
            $data['insurance_expires'],
            $data['tech_inspection_expires'],
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
            SET vehicle_id = ?, vehicle_number = ?, carrier_id = ?, carrier_name = ?, status_updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $result = $stmt->execute([
            $vehicleId, 
            $vehicle['vehicle_number'], 
            $vehicle['carrier_id'], 
            $vehicle['company_name'], 
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
                SET vehicle_id = NULL, vehicle_number = NULL, carrier_id = NULL, carrier_name = NULL, status_updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            return $stmt->execute([$orderId]);
        }
        
        return true;
    }
}