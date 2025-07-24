<?php

namespace App\CRM;

use PDO;

class RoleManager {
    public $db;
    
    public function __construct() {
        $this->db = \Database::getInstance()->getConnection();
    }
    
    /**
     * Проверка разрешения пользователя
     */
    public function hasPermission($userId, $resource, $action) {
        $stmt = $this->db->prepare("
            SELECT r.permissions 
            FROM user_roles ur
            JOIN roles r ON ur.role_id = r.id
            WHERE ur.user_id = ?
        ");
        $stmt->execute([$userId]);
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($roles as $role) {
            $permissions = json_decode($role['permissions'], true);
            
            // Если есть полный доступ
            if (isset($permissions['all']) && $permissions['all']) {
                return true;
            }
            
            // Проверка конкретного разрешения
            if (isset($permissions[$resource][$action]) && $permissions[$resource][$action]) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Получение всех ролей пользователя
     */
    public function getUserRoles($userId) {
        $stmt = $this->db->prepare("
            SELECT r.*, ur.created_at as assigned_at
            FROM user_roles ur
            JOIN roles r ON ur.role_id = r.id
            WHERE ur.user_id = ?
            ORDER BY r.name
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Получение всех доступных ролей
     */
    public function getAllRoles() {
        $stmt = $this->db->prepare("SELECT * FROM roles ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Назначение роли пользователю
     */
    public function assignRole($userId, $roleId, $assignedBy) {
        $stmt = $this->db->prepare("
            INSERT INTO user_roles (user_id, role_id, assigned_by) 
            VALUES (?, ?, ?)
            ON CONFLICT (user_id, role_id) DO NOTHING
        ");
        return $stmt->execute([$userId, $roleId, $assignedBy]);
    }
    
    /**
     * Отзыв роли у пользователя
     */
    public function revokeRole($userId, $roleId) {
        $stmt = $this->db->prepare("DELETE FROM user_roles WHERE user_id = ? AND role_id = ?");
        return $stmt->execute([$userId, $roleId]);
    }
    
    /**
     * Получение разрешений пользователя
     */
    public function getUserPermissions($userId) {
        $roles = $this->getUserRoles($userId);
        $permissions = [];
        
        foreach ($roles as $role) {
            $rolePermissions = json_decode($role['permissions'], true);
            $permissions = array_merge_recursive($permissions, $rolePermissions);
        }
        
        return $permissions;
    }
    
    /**
     * Проверка является ли пользователь администратором
     */
    public function isAdmin($userId) {
        return $this->hasRole($userId, ['super_admin', 'admin']);
    }
    
    /**
     * Проверка наличия роли у пользователя
     */
    public function hasRole($userId, $roleNames) {
        if (!is_array($roleNames)) {
            $roleNames = [$roleNames];
        }
        
        $placeholders = str_repeat('?,', count($roleNames) - 1) . '?';
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM user_roles ur
            JOIN roles r ON ur.role_id = r.id
            WHERE ur.user_id = ? AND r.name IN ($placeholders)
        ");
        
        $params = array_merge([$userId], $roleNames);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
    }
}