<?php

namespace App\CRM;

use App\Auth;
use App\CRM\RoleManager;

class CRMAuth extends Auth {
    private $roleManager;
    
    public function __construct() {
        $this->roleManager = new RoleManager();
    }
    
    /**
     * Проверка аутентификации с учетом ролей
     */
    public static function requireCRMAuth($requiredResource = null, $requiredAction = null) {
        if (!self::isAuthenticated()) {
            header('Location: /admin/login.php');
            exit;
        }
        
        if ($requiredResource && $requiredAction) {
            $crmAuth = new self();
            $userId = $_SESSION['user_id'];
            
            if (!$crmAuth->roleManager->hasPermission($userId, $requiredResource, $requiredAction)) {
                header('HTTP/1.1 403 Forbidden');
                include __DIR__ . '/../../public/admin/403.php';
                exit;
            }
        }
    }
    
    /**
     * Получение текущего пользователя с ролями
     */
    public static function getCurrentUser() {
        if (!self::isAuthenticated()) {
            return null;
        }
        
        $crmAuth = new self();
        $userId = $_SESSION['user_id'];
        
        // Получение данных пользователя
        $stmt = $crmAuth->roleManager->db->prepare("
            SELECT u.*, 
                   array_agg(r.name) FILTER (WHERE r.name IS NOT NULL) as roles
            FROM users u
            LEFT JOIN user_roles ur ON u.id = ur.user_id
            LEFT JOIN roles r ON ur.role_id = r.id
            WHERE u.id = ?
            GROUP BY u.id
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($user) {
            // Преобразование PostgreSQL массива в PHP массив
            if (is_string($user['roles'])) {
                $rolesString = trim($user['roles'], '{}');
                $user['roles'] = $rolesString ? explode(',', $rolesString) : [];
            }
            
            $user['permissions'] = $crmAuth->roleManager->getUserPermissions($userId);
            $user['is_admin'] = $crmAuth->roleManager->isAdmin($userId);
        }
        
        return $user;
    }
    
    /**
     * Проверка разрешения для текущего пользователя
     */
    public static function can($resource, $action) {
        if (!self::isAuthenticated()) {
            return false;
        }
        
        $crmAuth = new self();
        return $crmAuth->roleManager->hasPermission($_SESSION['user_id'], $resource, $action);
    }
    
    /**
     * Проверка роли для текущего пользователя
     */
    public static function hasRole($roleNames) {
        if (!self::isAuthenticated()) {
            return false;
        }
        
        $crmAuth = new self();
        return $crmAuth->roleManager->hasRole($_SESSION['user_id'], $roleNames);
    }
    
    /**
     * Авторизация с обновлением времени последнего входа
     */
    public static function loginWithActivity($username, $password) {
        $result = Auth::login($username, $password);
        
        if ($result) {
            // Обновление времени последнего входа
            $auth = new self();
            $stmt = $auth->roleManager->db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
        }
        
        return $result;
    }
}