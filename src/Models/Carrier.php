<?php

namespace App\Models;

use PDO;

class Carrier {
    private $db;
    
    public function __construct() {
        $this->db = \Database::getInstance()->getConnection();
    }
    
    public function getAll($status = null) {
        if ($status) {
            $stmt = $this->db->prepare("SELECT * FROM carriers WHERE status = ? ORDER BY company_name");
            $stmt->execute([$status]);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM carriers ORDER BY company_name");
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM carriers WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO carriers (company_name, contact_person, phone, email, address, license_number, rating, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['company_name'],
            $data['contact_person'],
            $data['phone'],
            $data['email'],
            $data['address'],
            $data['license_number'],
            $data['rating'] ?? 5.00,
            $data['status'] ?? 'active'
        ]);
    }
    
    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE carriers 
            SET company_name = ?, contact_person = ?, phone = ?, email = ?, 
                address = ?, license_number = ?, rating = ?, status = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['company_name'],
            $data['contact_person'],
            $data['phone'],
            $data['email'],
            $data['address'],
            $data['license_number'],
            $data['rating'],
            $data['status'],
            $id
        ]);
    }
    
    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE carriers SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }
    
    public function getActiveCarriers() {
        return $this->getAll('active');
    }
    
    public function getCarrierVehicles($carrierId) {
        $stmt = $this->db->prepare("
            SELECT v.*, c.company_name 
            FROM vehicles v 
            JOIN carriers c ON v.carrier_id = c.id 
            WHERE v.carrier_id = ? 
            ORDER BY v.vehicle_number
        ");
        $stmt->execute([$carrierId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getCarrierDrivers($carrierId) {
        $stmt = $this->db->prepare("
            SELECT d.*, c.company_name 
            FROM drivers d 
            JOIN carriers c ON d.carrier_id = c.id 
            WHERE d.carrier_id = ? 
            ORDER BY d.name
        ");
        $stmt->execute([$carrierId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getCarrierStats($carrierId) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT v.id) as total_vehicles,
                COUNT(DISTINCT CASE WHEN v.status = 'available' THEN v.id END) as available_vehicles,
                COUNT(DISTINCT d.id) as total_drivers,
                COUNT(DISTINCT CASE WHEN d.status = 'available' THEN d.id END) as available_drivers,
                COUNT(DISTINCT so.id) as total_orders,
                AVG(c.rating) as avg_rating
            FROM carriers c
            LEFT JOIN vehicles v ON c.id = v.carrier_id
            LEFT JOIN drivers d ON c.id = d.carrier_id
            LEFT JOIN shipment_orders so ON c.id = so.carrier_id
            WHERE c.id = ?
            GROUP BY c.id
        ");
        $stmt->execute([$carrierId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}