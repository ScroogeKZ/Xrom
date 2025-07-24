<?php

namespace App;

class ClientAuth {
    
    /**
     * Проверка авторизации клиента
     * @return bool
     */
    public static function isLoggedIn() {
        return isset($_SESSION['client_logged_in']) && $_SESSION['client_logged_in'] === true;
    }
    
    /**
     * Проверка и перенаправление неавторизованных пользователей
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: /client/login.php');
            exit;
        }
        
        // Проверяем срок действия сессии (24 часа)
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 86400) {
            self::logout();
            header('Location: /client/login.php?expired=1');
            exit;
        }
        
        // Обновляем время последней активности
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Логин клиента
     * @param array $client - данные клиента из базы
     */
    public static function login($client) {
        // Устанавливаем долгосрочную сессию (24 часа)
        ini_set('session.cookie_lifetime', 86400);
        ini_set('session.gc_maxlifetime', 86400);
        
        $_SESSION['client_logged_in'] = true;
        $_SESSION['client_id'] = $client['id'];
        $_SESSION['client_name'] = $client['name'];
        $_SESSION['client_phone'] = $client['phone'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Выход из системы
     */
    public static function logout() {
        unset($_SESSION['client_logged_in']);
        unset($_SESSION['client_id']);
        unset($_SESSION['client_name']);
        unset($_SESSION['client_phone']);
        unset($_SESSION['login_time']);
        unset($_SESSION['last_activity']);
        
        session_destroy();
    }
    
    /**
     * Получить ID текущего клиента
     */
    public static function getClientId() {
        return $_SESSION['client_id'] ?? null;
    }
    
    /**
     * Получить имя текущего клиента
     */
    public static function getClientName() {
        return $_SESSION['client_name'] ?? null;
    }
    
    /**
     * Получить телефон текущего клиента
     */
    public static function getClientPhone() {
        return $_SESSION['client_phone'] ?? null;
    }
}