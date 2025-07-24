<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/database.php';

use App\Models\ShipmentOrder;
use App\Models\Client;
use App\ClientAuth;

// Проверяем авторизацию
ClientAuth::requireLogin();

$clientId = ClientAuth::getClientId();
$clientName = ClientAuth::getClientName();

// Get client's orders
try {
    $shipmentOrder = new ShipmentOrder();
    $orders = $shipmentOrder->getByClientPhone(ClientAuth::getClientPhone());
} catch (Exception $e) {
    $orders = [];
    $error = "Ошибка загрузки заказов: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - Хром-KZ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        .status-new { @apply bg-blue-100 text-blue-800 border-blue-200; }
        .status-assigned { @apply bg-yellow-100 text-yellow-800 border-yellow-200; }
        .status-in_transit { @apply bg-purple-100 text-purple-800 border-purple-200; }
        .status-delivered { @apply bg-green-100 text-green-800 border-green-200; }
        .status-cancelled { @apply bg-red-100 text-red-800 border-red-200; }
        
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
        
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        
        .status-progress {
            background: linear-gradient(90deg, #10b981 0%, #10b981 var(--progress, 0%), #e5e7eb var(--progress, 0%), #e5e7eb 100%);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen" x-data="{ activeTab: 'orders', showNotifications: false }">
    <!-- Modern Header -->
    <header class="gradient-bg shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-3">
                        <img src="/assets/logo.png" alt="Хром-KZ" class="h-10 w-10 rounded-lg shadow-md" onerror="this.style.display='none'">
                        <div class="text-white">
                            <h1 class="text-xl font-bold">Личный кабинет</h1>
                            <p class="text-blue-100 text-sm">Добро пожаловать, <?= htmlspecialchars($clientName) ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Navigation Tabs -->
                <nav class="hidden md:flex space-x-1 bg-white/10 rounded-lg p-1">
                    <button @click="activeTab = 'orders'" 
                            :class="activeTab === 'orders' ? 'bg-white text-blue-700' : 'text-white hover:bg-white/20'"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-all">
                        Мои заказы
                    </button>
                    <button @click="activeTab = 'create'" 
                            :class="activeTab === 'create' ? 'bg-white text-blue-700' : 'text-white hover:bg-white/20'"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-all">
                        Новый заказ
                    </button>
                    <button @click="activeTab = 'tracking'" 
                            :class="activeTab === 'tracking' ? 'bg-white text-blue-700' : 'text-white hover:bg-white/20'"
                            class="px-4 py-2 rounded-md text-sm font-medium transition-all">
                        Отслеживание
                    </button>
                </nav>
                
                <div class="flex items-center space-x-4">
                    <!-- Notification Bell -->
                    <div class="relative">
                        <button @click="showNotifications = !showNotifications" 
                                class="text-white hover:text-blue-200 p-2 rounded-full hover:bg-white/10 transition-all">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M15 17h5l-3.5-3.5c-.1-.1-.2-.2-.2-.4V9c0-3.3-2.7-6-6-6s-6 2.7-6 6v3.6c0 .2-.1.3-.2.4L1 17h5m7 0v1c0 1.1-.9 2-2 2s-2-.9-2-2v-1m7 0H8" />
                            </svg>
                            <span class="absolute -top-1 -right-1 h-4 w-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">1</span>
                        </button>
                        
                        <!-- Notification Dropdown -->
                        <div x-show="showNotifications" @click.away="showNotifications = false"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-200 z-50">
                            <div class="p-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">Уведомления</h3>
                            </div>
                            <div class="p-4">
                                <div class="flex items-start space-x-3 p-3 bg-blue-50 rounded-lg">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 2L3 7v11c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V7l-7-5z"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">Заказ в пути</p>
                                        <p class="text-sm text-gray-600">Ваш заказ №7 находится в пути</p>
                                        <p class="text-xs text-gray-500 mt-1">2 минуты назад</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <a href="/" class="text-white hover:text-blue-200 text-sm font-medium">На главную</a>
                    <a href="/client/logout.php" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all">
                        Выйти
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100">
                        <svg class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m13-8V4a1 1 0 00-1-1H7a1 1 0 00-1 1v1m8 0V4.5" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Всего заказов</p>
                        <p class="text-2xl font-bold text-gray-900"><?= count($orders) ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">В пути</p>
                        <p class="text-2xl font-bold text-gray-900"><?= count(array_filter($orders, fn($o) => in_array($o['status'], ['assigned', 'picked_up', 'in_transit']))) ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100">
                        <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Доставлено</p>
                        <p class="text-2xl font-bold text-gray-900"><?= count(array_filter($orders, fn($o) => $o['status'] === 'delivered')) ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100">
                        <svg class="w-6 h-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Активность</p>
                        <p class="text-2xl font-bold text-gray-900">98%</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Content -->
        <div x-show="activeTab === 'orders'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0">
            <!-- Quick Actions for Orders Tab -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <a href="/astana.php" class="bg-white rounded-xl shadow-sm p-6 card-hover border-l-4 border-blue-500">
                    <div class="flex items-center space-x-4">
                        <div class="p-3 bg-blue-100 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-1">Доставка по Астане</h3>
                            <p class="text-sm text-gray-600">Быстрая доставка в пределах города</p>
                        </div>
                    </div>
                </a>
                <a href="/regional.php" class="bg-white rounded-xl shadow-sm p-6 card-hover border-l-4 border-green-500">
                    <div class="flex items-center space-x-4">
                        <div class="p-3 bg-green-100 rounded-lg">
                            <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-1">Межгородская доставка</h3>
                            <p class="text-sm text-gray-600">Отправка груза в другие города</p>
                        </div>
                    </div>
                </a>
                <a href="/tracking.php" class="bg-white rounded-xl shadow-sm p-6 card-hover border-l-4 border-purple-500">
                    <div class="flex items-center space-x-4">
                        <div class="p-3 bg-purple-100 rounded-lg">
                            <svg class="w-6 h-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-1">Отследить заказ</h3>
                            <p class="text-sm text-gray-600">Проверить статус доставки</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Orders Section -->
        <div x-show="activeTab === 'orders'" class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-900">Мои заказы</h2>
                    <div class="flex items-center space-x-3">
                        <select class="text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white">
                            <option>Все статусы</option>
                            <option>Новые</option>
                            <option>В пути</option>
                            <option>Доставлено</option>
                        </select>
                        <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                            Создать заказ
                        </button>
                    </div>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="px-6 py-4 bg-red-50 border-b border-red-200 text-red-800">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (empty($orders)): ?>
                <div class="px-6 py-16 text-center">
                    <div class="mx-auto w-32 h-32 bg-gradient-to-br from-blue-100 to-purple-100 rounded-full flex items-center justify-center mb-6">
                        <svg class="w-16 h-16 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m13-8V4a1 1 0 00-1-1H7a1 1 0 00-1 1v1m8 0V4.5" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Добро пожаловать в систему доставки!</h3>
                    <p class="text-gray-600 mb-8 max-w-md mx-auto">Начните с создания первого заказа. Мы обеспечим быструю и надежную доставку ваших грузов.</p>
                    <div class="flex justify-center space-x-4">
                        <a href="/astana.php" class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-8 py-3 rounded-lg font-medium hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg">
                            Создать заказ
                        </a>
                        <a href="/tracking.php" class="bg-white text-gray-700 px-8 py-3 rounded-lg font-medium border border-gray-300 hover:bg-gray-50 transition-all">
                            Отследить заказ
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="space-y-4 p-6">
                    <?php foreach ($orders as $order): ?>
                        <div class="bg-gradient-to-r from-white to-gray-50 border border-gray-200 rounded-xl p-6 card-hover">
                            <!-- Header заказа -->
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6">
                                <div class="flex items-center space-x-4">
                                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                                        <span class="text-white font-bold text-lg"><?= $order['id'] ?></span>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900">Заказ №<?= $order['id'] ?></h3>
                                        <p class="text-sm text-gray-600"><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-3 mt-4 sm:mt-0">
                                    <span class="px-4 py-2 text-sm font-semibold rounded-full border status-<?= $order['status'] ?>">
                                        <?= htmlspecialchars(ShipmentOrder::getStatusName($order['status'])) ?>
                                    </span>
                                    
                                    <!-- Progress indicator -->
                                    <?php 
                                    $progressMap = [
                                        'new' => 10, 'confirmed' => 25, 'assigned' => 40, 
                                        'picked_up' => 60, 'in_transit' => 80, 'delivered' => 100
                                    ];
                                    $progress = $progressMap[$order['status']] ?? 10;
                                    ?>
                                    <div class="w-20 h-2 bg-gray-200 rounded-full">
                                        <div class="h-2 bg-gradient-to-r from-blue-500 to-green-500 rounded-full transition-all duration-500" 
                                             style="width: <?= $progress ?>%"></div>
                                    </div>
                                    <span class="text-xs text-gray-500 font-medium"><?= $progress ?>%</span>
                                </div>
                            </div>

                            <!-- Основная информация заказа -->
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                                <!-- Маршрут -->
                                <div class="space-y-4">
                                    <h4 class="font-semibold text-gray-900 flex items-center">
                                        <svg class="w-5 h-5 text-blue-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        </svg>
                                        Маршрут доставки
                                    </h4>
                                    <div class="bg-blue-50 rounded-lg p-4">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 w-3 h-3 bg-green-500 rounded-full mt-2"></div>
                                            <div class="ml-3 flex-1">
                                                <p class="text-sm font-medium text-gray-900">Откуда</p>
                                                <p class="text-sm text-gray-700"><?= htmlspecialchars($order['pickup_address']) ?></p>
                                                <?php if ($order['pickup_city']): ?>
                                                    <p class="text-xs text-gray-500"><?= htmlspecialchars($order['pickup_city']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center justify-center my-2">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                            </svg>
                                        </div>
                                        
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 w-3 h-3 bg-red-500 rounded-full mt-2"></div>
                                            <div class="ml-3 flex-1">
                                                <p class="text-sm font-medium text-gray-900">Куда</p>
                                                <p class="text-sm text-gray-700"><?= htmlspecialchars($order['delivery_address'] ?? 'В пределах города') ?></p>
                                                <?php if ($order['destination_city']): ?>
                                                    <p class="text-xs text-gray-500"><?= htmlspecialchars($order['destination_city']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Детали груза -->
                                <div class="space-y-4">
                                    <h4 class="font-semibold text-gray-900 flex items-center">
                                        <svg class="w-5 h-5 text-purple-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                        Информация о грузе
                                    </h4>
                                    <div class="bg-purple-50 rounded-lg p-4 space-y-3">
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-600">Тип груза:</span>
                                            <span class="text-sm font-medium text-gray-900"><?= htmlspecialchars($order['cargo_type']) ?></span>
                                        </div>
                                        <?php if ($order['weight']): ?>
                                            <div class="flex justify-between">
                                                <span class="text-sm text-gray-600">Вес:</span>
                                                <span class="text-sm font-medium text-gray-900"><?= $order['weight'] ?> кг</span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($order['dimensions']): ?>
                                            <div class="flex justify-between">
                                                <span class="text-sm text-gray-600">Размеры:</span>
                                                <span class="text-sm font-medium text-gray-900"><?= htmlspecialchars($order['dimensions']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-600">Время готовности:</span>
                                            <span class="text-sm font-medium text-gray-900"><?= htmlspecialchars($order['ready_time']) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- CRM Integration Section -->
                            <?php if ($order['carrier_name'] || $order['driver_name'] || $order['brand']): ?>
                                <div class="bg-gradient-to-r from-green-50 to-blue-50 border border-green-200 rounded-xl p-6">
                                    <h4 class="font-semibold text-gray-900 mb-4 flex items-center">
                                        <svg class="w-5 h-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                        Команда доставки
                                    </h4>
                                    
                                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                                        <?php if ($order['carrier_name']): ?>
                                            <div class="bg-white rounded-lg p-4 border border-green-100">
                                                <div class="flex items-center mb-3">
                                                    <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                                                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                        </svg>
                                                    </div>
                                                    <div class="ml-3">
                                                        <p class="text-sm font-medium text-gray-600">Перевозчик</p>
                                                    </div>
                                                </div>
                                                <p class="font-semibold text-gray-900 mb-1"><?= htmlspecialchars($order['carrier_name']) ?></p>
                                                <?php if ($order['carrier_phone']): ?>
                                                    <p class="text-sm text-gray-600 mb-2">
                                                        📞 <a href="tel:<?= $order['carrier_phone'] ?>" class="hover:text-green-600"><?= htmlspecialchars($order['carrier_phone']) ?></a>
                                                    </p>
                                                <?php endif; ?>
                                                <?php if ($order['rating']): ?>
                                                    <div class="flex items-center">
                                                        <div class="flex text-yellow-400">
                                                            <?php for($i = 1; $i <= 5; $i++): ?>
                                                                <svg class="w-4 h-4 <?= $i <= floor($order['rating']) ? 'text-yellow-400' : 'text-gray-300' ?>" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                                </svg>
                                                            <?php endfor; ?>
                                                        </div>
                                                        <span class="ml-2 text-sm text-gray-600"><?= $order['rating'] ?>/5</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($order['driver_name']): ?>
                                            <div class="bg-white rounded-lg p-4 border border-blue-100">
                                                <div class="flex items-center mb-3">
                                                    <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                                                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                        </svg>
                                                    </div>
                                                    <div class="ml-3">
                                                        <p class="text-sm font-medium text-gray-600">Водитель</p>
                                                    </div>
                                                </div>
                                                <p class="font-semibold text-gray-900 mb-1"><?= htmlspecialchars($order['driver_name']) ?></p>
                                                <?php if ($order['driver_phone']): ?>
                                                    <p class="text-sm text-gray-600 mb-2">
                                                        📞 <a href="tel:<?= $order['driver_phone'] ?>" class="hover:text-blue-600"><?= htmlspecialchars($order['driver_phone']) ?></a>
                                                    </p>
                                                <?php endif; ?>
                                                <?php if ($order['driver_license']): ?>
                                                    <p class="text-xs text-gray-500">Права: <?= htmlspecialchars($order['driver_license']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($order['brand'] || $order['license_plate']): ?>
                                            <div class="bg-white rounded-lg p-4 border border-purple-100">
                                                <div class="flex items-center mb-3">
                                                    <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center">
                                                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                                        </svg>
                                                    </div>
                                                    <div class="ml-3">
                                                        <p class="text-sm font-medium text-gray-600">Транспорт</p>
                                                    </div>
                                                </div>
                                                <?php if ($order['brand']): ?>
                                                    <p class="font-semibold text-gray-900 mb-1"><?= htmlspecialchars($order['brand']) ?> <?= htmlspecialchars($order['model'] ?? '') ?></p>
                                                    <?php if ($order['year']): ?>
                                                        <p class="text-xs text-gray-500 mb-2"><?= $order['year'] ?> г.</p>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                <?php if ($order['license_plate']): ?>
                                                    <p class="text-sm font-mono bg-gray-100 px-2 py-1 rounded mb-2"><?= htmlspecialchars($order['license_plate']) ?></p>
                                                <?php endif; ?>
                                                <?php if ($order['vehicle_type']): ?>
                                                    <p class="text-xs text-purple-600 font-medium"><?= htmlspecialchars($order['vehicle_type']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Action buttons -->
                            <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
                                <div class="flex space-x-3">
                                    <a href="/client/order_details.php?id=<?= $order['id'] ?>" 
                                       class="bg-blue-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-all flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Подробнее
                                    </a>
                                    
                                    <a href="/tracking.php?order=<?= $order['id'] ?>" 
                                       class="bg-purple-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-purple-700 transition-all flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        </svg>
                                        Отследить
                                    </a>
                                </div>
                                
                                <div class="text-right">
                                    <?php if ($order['status_updated_at']): ?>
                                        <p class="text-xs text-gray-500 mb-1">Последнее обновление</p>
                                        <p class="text-sm font-medium text-gray-700">
                                            <?= date('d.m.Y H:i', strtotime($order['status_updated_at'])) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- New Tab Content - Create Order -->
        <div x-show="activeTab === 'create'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Создать новый заказ</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <a href="/astana.php" class="group bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-8 border-2 border-blue-200 hover:border-blue-400 card-hover">
                        <div class="flex items-center justify-center w-16 h-16 bg-blue-500 rounded-xl mb-6 mx-auto group-hover:bg-blue-600 transition-colors">
                            <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3 text-center">Доставка по Астане</h3>
                        <p class="text-gray-600 text-center mb-4">Быстрая доставка в пределах города с отслеживанием в реальном времени</p>
                        <div class="flex items-center justify-center text-blue-600 font-medium">
                            <span>Создать заказ</span>
                            <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </a>
                    
                    <a href="/regional.php" class="group bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-8 border-2 border-green-200 hover:border-green-400 card-hover">
                        <div class="flex items-center justify-center w-16 h-16 bg-green-500 rounded-xl mb-6 mx-auto group-hover:bg-green-600 transition-colors">
                            <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3 text-center">Межгородская доставка</h3>
                        <p class="text-gray-600 text-center mb-4">Надежная доставка по всему Казахстану с профессиональными перевозчиками</p>
                        <div class="flex items-center justify-center text-green-600 font-medium">
                            <span>Создать заказ</span>
                            <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- New Tab Content - Tracking -->
        <div x-show="activeTab === 'tracking'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Отслеживание заказов</h2>
                
                <div class="max-w-md mx-auto">
                    <div class="relative">
                        <input type="text" placeholder="Введите номер заказа..." 
                               class="w-full px-4 py-4 text-lg border-2 border-gray-300 rounded-xl focus:border-purple-500 focus:outline-none pl-12">
                        <svg class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <button class="w-full mt-4 bg-purple-600 text-white py-4 rounded-xl font-medium hover:bg-purple-700 transition-colors">
                        Отследить заказ
                    </button>
                </div>
                
                <div class="mt-8 text-center">
                    <p class="text-gray-600 mb-4">Или используйте прямую ссылку:</p>
                    <a href="/tracking.php" class="text-purple-600 hover:text-purple-700 font-medium">
                        Перейти к полной системе отслеживания →
                    </a>
                </div>
            </div>
        </div>

        <!-- Modern Contact Info -->
        <div class="mt-12 bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl shadow-lg text-white p-8">
            <div class="max-w-4xl mx-auto">
                <div class="text-center mb-8">
                    <h3 class="text-2xl font-bold mb-3">Нужна помощь?</h3>
                    <p class="text-blue-100">Наша служба поддержки работает круглосуточно для вашего удобства</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </div>
                        <h4 class="font-semibold mb-2">Телефон</h4>
                        <p class="text-blue-100">+7 (7172) 123-456</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h4 class="font-semibold mb-2">Email</h4>
                        <p class="text-blue-100">support@chrome-kz.com</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h4 class="font-semibold mb-2">Режим работы</h4>
                        <p class="text-blue-100">24/7 онлайн поддержка</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function cancelOrder(orderId) {
            if (confirm('Вы уверены, что хотите отменить этот заказ?')) {
                fetch('/api/orders.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: orderId,
                        status: 'cancelled'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Ошибка при отмене заказа');
                    }
                })
                .catch(error => {
                    alert('Ошибка при отмене заказа');
                });
            }
        }
    </script>
</body>
</html>