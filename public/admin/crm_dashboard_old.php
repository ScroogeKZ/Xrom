<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\CRM\CRMAuth;
use App\Models\ShipmentOrder;
use App\Models\Driver;
use App\Models\Carrier;
use App\Models\Vehicle;

// Проверка авторизации
CRMAuth::requireCRMAuth();

$shipmentOrder = new ShipmentOrder();
$driver = new Driver();
$carrier = new Carrier();
$vehicle = new Vehicle();

// Получаем статистику
$totalOrders = count($shipmentOrder->getAll());
$newOrders = count($shipmentOrder->getByStatus('new'));
$inProgressOrders = count($shipmentOrder->getByStatus('assigned')) + count($shipmentOrder->getByStatus('picked_up')) + count($shipmentOrder->getByStatus('in_transit'));
$completedOrders = count($shipmentOrder->getByStatus('delivered'));

$totalDrivers = count($driver->getAll());
$availableDrivers = count($driver->getAll('available'));
$busyDrivers = count($driver->getAll('busy'));

$totalVehicles = count($vehicle->getAll());
$availableVehicles = count($vehicle->getAll('available'));
$busyVehicles = count($vehicle->getAll('busy'));

$totalCarriers = count($carrier->getAll());
$activeCarriers = count($carrier->getAll('active'));

// Последние заказы
$recentOrders = array_slice($shipmentOrder->getAll(), -5);
$recentOrders = array_reverse($recentOrders);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Панель - Хром-KZ Логистика</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50" x-data="{ sidebarOpen: true }">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 z-30 w-64 bg-white shadow-lg transform transition-transform duration-300 ease-in-out" :class="{ '-translate-x-full': !sidebarOpen }">
        <!-- Logo -->
        <div class="flex items-center justify-center h-16 px-4 bg-blue-600">
            <img src="/assets/logo.png" alt="Хром-KZ" class="h-8 w-auto">
            <span class="ml-2 text-xl font-bold text-white">Хром-KZ CRM</span>
        </div>
        
        <!-- Navigation -->
        <nav class="mt-5 px-2">
            <div class="space-y-1">
                <a href="/admin/crm_dashboard.php" class="bg-blue-100 text-blue-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                    <i class="fas fa-tachometer-alt mr-3 text-blue-500"></i>
                    Дашборд
                </a>
                
                <div class="pt-4">
                    <p class="px-2 text-xs font-semibold text-gray-600 uppercase tracking-wider">Заказы</p>
                    <a href="/admin/crm_orders.php" class="text-gray-700 hover:bg-gray-50 group flex items-center px-2 py-2 text-sm font-medium rounded-md mt-1">
                        <i class="fas fa-box mr-3 text-gray-400"></i>
                        Все заказы
                    </a>
                    <a href="/admin/crm_orders.php?status=new" class="text-gray-700 hover:bg-gray-50 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-plus-circle mr-3 text-gray-400"></i>
                        Новые заказы
                    </a>
                    <a href="/admin/crm_orders.php?status=in_progress" class="text-gray-700 hover:bg-gray-50 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-truck mr-3 text-gray-400"></i>
                        В работе
                    </a>
                </div>
                
                <div class="pt-4">
                    <p class="px-2 text-xs font-semibold text-gray-600 uppercase tracking-wider">Ресурсы</p>
                    <a href="/admin/crm_carriers.php" class="text-gray-700 hover:bg-gray-50 group flex items-center px-2 py-2 text-sm font-medium rounded-md mt-1">
                        <i class="fas fa-building mr-3 text-gray-400"></i>
                        Перевозчики
                    </a>
                    <a href="/admin/crm_vehicles.php" class="text-gray-700 hover:bg-gray-50 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-car mr-3 text-gray-400"></i>
                        Транспорт
                    </a>
                    <a href="/admin/crm_drivers.php" class="text-gray-700 hover:bg-gray-50 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-users mr-3 text-gray-400"></i>
                        Водители
                    </a>
                </div>
                
                <div class="pt-4">
                    <p class="px-2 text-xs font-semibold text-gray-600 uppercase tracking-wider">Аналитика</p>
                    <a href="/admin/crm_analytics.php" class="text-gray-700 hover:bg-gray-50 group flex items-center px-2 py-2 text-sm font-medium rounded-md mt-1">
                        <i class="fas fa-chart-bar mr-3 text-gray-400"></i>
                        Отчеты
                    </a>
                    <a href="/admin/crm_calendar.php" class="text-gray-700 hover:bg-gray-50 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                        <i class="fas fa-calendar mr-3 text-gray-400"></i>
                        Календарь
                    </a>
                </div>
                
                <div class="pt-4">
                    <p class="px-2 text-xs font-semibold text-gray-600 uppercase tracking-wider">Система</p>
                    <a href="/admin/crm_settings.php" class="text-gray-700 hover:bg-gray-50 group flex items-center px-2 py-2 text-sm font-medium rounded-md mt-1">
                        <i class="fas fa-cog mr-3 text-gray-400"></i>
                        Настройки
                    </a>
                </div>
            </div>
        </nav>
    </div>

    <!-- Main content -->
    <div class="flex-1 flex flex-col" :class="{ 'ml-64': sidebarOpen, 'ml-0': !sidebarOpen }">
        <!-- Top bar -->
        <div class="bg-white shadow-sm border-b border-gray-200">
            <div class="flex items-center justify-between h-16 px-4">
                <div class="flex items-center">
                    <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="ml-4 text-2xl font-semibold text-gray-900">Панель управления</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button class="bg-gray-100 p-2 rounded-full text-gray-600 hover:text-gray-900">
                            <i class="fas fa-bell"></i>
                        </button>
                        <span class="absolute -top-1 -right-1 h-4 w-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">
                            <?php echo $newOrders; ?>
                        </span>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <div class="h-8 w-8 bg-blue-500 rounded-full flex items-center justify-center">
                            <span class="text-white text-sm font-medium">A</span>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Администратор</span>
                        <a href="/admin/logout.php" class="text-gray-500 hover:text-red-600">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard content -->
        <div class="flex-1 p-6">
            <!-- Stats cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Заказы -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                    <i class="fas fa-box text-white"></i>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Всего заказов</dt>
                                    <dd class="text-lg font-medium text-gray-900"><?php echo $totalOrders; ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-5 py-3">
                        <div class="text-sm">
                            <span class="font-medium text-green-600"><?php echo $newOrders; ?></span>
                            <span class="text-gray-500"> новых</span>
                        </div>
                    </div>
                </div>

                <!-- Водители -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                    <i class="fas fa-users text-white"></i>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Водители</dt>
                                    <dd class="text-lg font-medium text-gray-900"><?php echo $totalDrivers; ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-5 py-3">
                        <div class="text-sm">
                            <span class="font-medium text-green-600"><?php echo $availableDrivers; ?></span>
                            <span class="text-gray-500"> свободно</span>
                        </div>
                    </div>
                </div>

                <!-- Транспорт -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                                    <i class="fas fa-truck text-white"></i>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Транспорт</dt>
                                    <dd class="text-lg font-medium text-gray-900"><?php echo $totalVehicles; ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-5 py-3">
                        <div class="text-sm">
                            <span class="font-medium text-green-600"><?php echo $availableVehicles; ?></span>
                            <span class="text-gray-500"> доступно</span>
                        </div>
                    </div>
                </div>

                <!-- Перевозчики -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                    <i class="fas fa-building text-white"></i>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Перевозчики</dt>
                                    <dd class="text-lg font-medium text-gray-900"><?php echo $totalCarriers; ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-5 py-3">
                        <div class="text-sm">
                            <span class="font-medium text-green-600"><?php echo $activeCarriers; ?></span>
                            <span class="text-gray-500"> активных</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick actions and recent orders -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Quick actions -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Быстрые действия</h3>
                        <div class="space-y-3">
                            <a href="/admin/crm_orders.php?action=create" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 text-center block">
                                <i class="fas fa-plus mr-2"></i>Создать заказ
                            </a>
                            <a href="/admin/crm_carriers.php?action=create" class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 text-center block">
                                <i class="fas fa-building mr-2"></i>Добавить перевозчика
                            </a>
                            <a href="/admin/crm_vehicles.php?action=create" class="w-full bg-yellow-600 text-white py-2 px-4 rounded-md hover:bg-yellow-700 text-center block">
                                <i class="fas fa-truck mr-2"></i>Добавить транспорт
                            </a>
                            <a href="/admin/crm_drivers.php?action=create" class="w-full bg-purple-600 text-white py-2 px-4 rounded-md hover:bg-purple-700 text-center block">
                                <i class="fas fa-user-plus mr-2"></i>Добавить водителя
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Recent orders -->
                <div class="lg:col-span-2 bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Последние заказы</h3>
                        <div class="overflow-hidden">
                            <ul class="divide-y divide-gray-200">
                                <?php foreach ($recentOrders as $order): ?>
                                <li class="py-4">
                                    <div class="flex items-center space-x-4">
                                        <div class="flex-shrink-0">
                                            <div class="h-8 w-8 rounded-full <?php echo $order['status'] === 'new' ? 'bg-blue-100' : ($order['status'] === 'delivered' ? 'bg-green-100' : 'bg-yellow-100'); ?> flex items-center justify-center">
                                                <i class="fas fa-box text-sm <?php echo $order['status'] === 'new' ? 'text-blue-600' : ($order['status'] === 'delivered' ? 'text-green-600' : 'text-yellow-600'); ?>"></i>
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">
                                                Заказ #<?php echo $order['id']; ?> - <?php echo htmlspecialchars($order['contact_name']); ?>
                                            </p>
                                            <p class="text-sm text-gray-500 truncate">
                                                <?php echo htmlspecialchars($order['pickup_address']); ?>
                                            </p>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                                <?php 
                                                switch($order['status']) {
                                                    case 'new': echo 'bg-blue-100 text-blue-800'; break;
                                                    case 'delivered': echo 'bg-green-100 text-green-800'; break;
                                                    case 'assigned': echo 'bg-yellow-100 text-yellow-800'; break;
                                                    default: echo 'bg-gray-100 text-gray-800';
                                                }
                                                ?>">
                                                <?php 
                                                switch($order['status']) {
                                                    case 'new': echo 'Новый'; break;
                                                    case 'delivered': echo 'Доставлен'; break;
                                                    case 'assigned': echo 'Назначен'; break;
                                                    case 'processing': echo 'Обработка'; break;
                                                    default: echo ucfirst($order['status']);
                                                }
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="mt-6">
                            <a href="/admin/crm_orders.php" class="w-full bg-white border border-gray-300 rounded-md py-2 px-4 inline-flex justify-center text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Посмотреть все заказы
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>