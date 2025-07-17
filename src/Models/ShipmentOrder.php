<?php

namespace App\Models;

require_once __DIR__ . '/../../config/database.php';

class ShipmentOrder {
    private $db;
    
    public function __construct() {
        $this->db = \Database::getInstance()->getConnection();
    }
    
    public function create($data) {
        $sql = "INSERT INTO shipment_orders (
            order_type, pickup_address, ready_time, cargo_type, weight, dimensions,
            contact_name, contact_phone, notes, pickup_city, destination_city,
            delivery_address, delivery_method, desired_arrival_date, status
        ) VALUES (
            :order_type, :pickup_address, :ready_time, :cargo_type, :weight, :dimensions,
            :contact_name, :contact_phone, :notes, :pickup_city, :destination_city,
            :delivery_address, :delivery_method, :desired_arrival_date, :status
        ) RETURNING *";
        
        try {
            $stmt = $this->db->prepare($sql);
            
            $status = $data['status'] ?? 'new';
            
            $stmt->execute([
                ':order_type' => $data['order_type'],
                ':pickup_address' => $data['pickup_address'],
                ':ready_time' => $data['ready_time'],
                ':cargo_type' => $data['cargo_type'],
                ':weight' => $data['weight'],
                ':dimensions' => $data['dimensions'],
                ':contact_name' => $data['contact_name'],
                ':contact_phone' => $data['contact_phone'],
                ':notes' => $data['notes'] ?? null,
                ':pickup_city' => $data['pickup_city'] ?? null,
                ':destination_city' => $data['destination_city'] ?? null,
                ':delivery_address' => $data['delivery_address'] ?? null,
                ':delivery_method' => $data['delivery_method'] ?? null,
                ':desired_arrival_date' => $data['desired_arrival_date'] ?? null,
                ':status' => $status
            ]);
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error creating shipment order: " . $e->getMessage());
            throw new Exception("Failed to create shipment order");
        }
    }
    
    public function getAll($filters = []) {
        $sql = "SELECT * FROM shipment_orders WHERE 1=1";
        $params = [];
        
        if (isset($filters['order_type'])) {
            $sql .= " AND order_type = :order_type";
            $params[':order_type'] = $filters['order_type'];
        }
        
        if (isset($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (isset($filters['search'])) {
            $sql .= " AND (contact_name ILIKE :search OR contact_phone ILIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        if (isset($filters['limit'])) {
            $sql .= " LIMIT :limit";
            $params[':limit'] = (int)$filters['limit'];
        }
        
        if (isset($filters['offset'])) {
            $sql .= " OFFSET :offset";
            $params[':offset'] = (int)$filters['offset'];
        }
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching shipment orders: " . $e->getMessage());
            throw new Exception("Failed to fetch shipment orders");
        }
    }
    
    public function getById($id) {
        $sql = "SELECT * FROM shipment_orders WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error fetching shipment order: " . $e->getMessage());
            throw new Exception("Failed to fetch shipment order");
        }
    }
    
    public function updateStatus($id, $status) {
        $sql = "UPDATE shipment_orders SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id RETURNING *";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':status' => $status
            ]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error updating shipment order status: " . $e->getMessage());
            throw new Exception("Failed to update shipment order status");
        }
    }
    
    public function getCount($filters = []) {
        $sql = "SELECT COUNT(*) as count FROM shipment_orders WHERE 1=1";
        $params = [];
        
        if (isset($filters['order_type'])) {
            $sql .= " AND order_type = :order_type";
            $params[':order_type'] = $filters['order_type'];
        }
        
        if (isset($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (isset($filters['search'])) {
            $sql .= " AND (contact_name ILIKE :search OR contact_phone ILIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return $result['count'];
        } catch (PDOException $e) {
            error_log("Error counting shipment orders: " . $e->getMessage());
            throw new Exception("Failed to count shipment orders");
        }
    }
}