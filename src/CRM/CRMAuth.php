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
        
        // В упрощенной версии все авторизованные админы имеют полный доступ
        // Проверка ресурсов отключена до реализации полной системы ролей
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
        
        // Получение данных пользователя (упрощенная версия без ролей)
        $stmt = $crmAuth->roleManager->db->prepare("
            SELECT u.*, 
                   COALESCE(u.first_name, 'Админ') as first_name,
                   COALESCE(u.last_name, 'Пользователь') as last_name,
                   ARRAY['admin'] as roles
            FROM users u
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($user) {
            // Для админов даем все права
            $user['roles'] = ['admin'];
            $user['permissions'] = ['create', 'read', 'update', 'delete'];
            $user['is_admin'] = true;
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
        
        // В упрощенной версии все админы имеют все права
        return true;
    }
    
    /**
     * Проверка роли для текущего пользователя
     */
    public static function hasRole($roleNames) {
        if (!self::isAuthenticated()) {
            return false;
        }
        
        // В упрощенной версии все админы имеют все роли
        return true;
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