<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';

use App\ClientAuth;

// Выход из системы
ClientAuth::logout();

// Перенаправляем на главную страницу
header('Location: /');
exit;
?>