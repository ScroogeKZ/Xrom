<?php

namespace App\Models;

require_once __DIR__ . '/../../config/database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = \Database::getInstance()->getConnection();
    }
    
    public function create($username, $password) {
        $sql = "INSERT INTO users (username, password) VALUES (:username, :password) RETURNING *";
        
        try {
            $stmt = $this->db->prepare($sql);
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt->execute([
                ':username' => $username,
                ':password' => $hashedPassword
            ]);
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            throw new Exception("Failed to create user");
        }
    }
    
    public function findByUsername($username) {
        $sql = "SELECT * FROM users WHERE username = :username";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':username' => $username]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error finding user: " . $e->getMessage());
            throw new Exception("Failed to find user");
        }
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM users WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error finding user by ID: " . $e->getMessage());
            throw new Exception("Failed to find user");
        }
    }
    
    public function verifyPassword($plainPassword, $hashedPassword) {
        return password_verify($plainPassword, $hashedPassword);
    }
}