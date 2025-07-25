<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\CRM\CRMAuth;
use App\Models\DashboardWidget;
use App\Models\ShipmentOrder;

// Проверка авторизации
CRMAuth::requireCRMAuth();

$currentUser = CRMAuth::getCurrentUser();
$widgetModel = new DashboardWidget();
$orderModel = new ShipmentOrder();

// Получаем статистику заказов
$db = Database::getInstance()->getConnection();

// Подсчет заказов по статусам
$stmt = $db->prepare("SELECT status, COUNT(*) as count FROM shipment_orders GROUP BY status");
$stmt->execute();
$orderStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$newOrders = 0;
$inProgressOrders = 0; 
$completedOrders = 0;

foreach ($orderStats as $stat) {
    switch ($stat['status']) {
        case 'new':
        case 'pending':
            $newOrders += $stat['count'];
            break;
        case 'in_progress':
        case 'picked_up':
        case 'in_transit':
            $inProgressOrders += $stat['count'];
            break;
        case 'completed':
        case 'delivered':
            $completedOrders += $stat['count'];
            break;
    }
}

// Подсчет ресурсов
$stmt = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN is_active = true THEN 1 ELSE 0 END) as active FROM carriers");
$stmt->execute();
$carrierStats = $stmt->fetch(PDO::FETCH_ASSOC);
$totalCarriers = $carrierStats['total'];
$activeCarriers = $carrierStats['active'];

$stmt = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN is_active = true THEN 1 ELSE 0 END) as active FROM vehicles");
$stmt->execute();
$vehicleStats = $stmt->fetch(PDO::FETCH_ASSOC);
$totalVehicles = $vehicleStats['total'];
$availableVehicles = $vehicleStats['active'];

$stmt = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN is_active = true THEN 1 ELSE 0 END) as active FROM drivers");
$stmt->execute();
$driverStats = $stmt->fetch(PDO::FETCH_ASSOC);
$totalDrivers = $driverStats['total'];
$availableDrivers = $driverStats['active'];

// Получаем пользовательские виджеты
$userWidgets = $widgetModel->getUserWidgets($currentUser['id']);

// Если у пользователя нет настроек, создаем дефолтные виджеты
if (empty($userWidgets)) {
    $defaultWidgets = [
        ['type' => 'orders_stats', 'config' => [], 'enabled' => true],
        ['type' => 'recent_orders', 'config' => ['limit' => 5], 'enabled' => true],
        ['type' => 'carriers_stats', 'config' => [], 'enabled' => true],
        ['type' => 'quick_actions', 'config' => [], 'enabled' => true]
    ];
    $widgetModel->saveUserWidgets($currentUser['id'], $defaultWidgets);
    $userWidgets = $widgetModel->getUserWidgets($currentUser['id']);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Панель управления - Хром-KZ Логистика</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50">
    <div class="flex">
        <!-- Боковое меню -->
        <?php include 'components/crm_sidebar.php'; ?>

        <!-- Основной контент -->
        <div class="flex-1 ml-64">
            <!-- Верхняя панель -->
            <?php include 'components/crm_header.php'; ?>

            <!-- Основной контент -->
            <div class="p-6">
                <!-- Приветствие и настройки -->
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Добро пожаловать в CRM систему</h1>
                        <p class="text-gray-600">Обзор деятельности логистической компании Хром-KZ</p>
                    </div>
                    <div class="flex space-x-3">
                        <a href="/admin/dashboard_customize.php" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 flex items-center">
                            <i class="fas fa-cog mr-2"></i>
                            Настроить дашборд
                        </a>
                    </div>
                </div>

                <!-- Пользовательские виджеты -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($userWidgets as $widget): ?>
                        <?php if ($widget['is_enabled'] ?? true): ?>
                            <?php
                                $config = json_decode($widget['widget_config'], true) ?? [];
                                $widgetData = $widgetModel->getWidgetData($widget['widget_type'], $config);
                                $widgetType = $widget['widget_type'];
                            ?>
                            
                            <?php if ($widgetType === 'orders_stats'): ?>
                                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Статистика заказов</h3>
                                    <div class="space-y-3">
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Всего заказов</span>
                                            <span class="font-bold text-2xl text-blue-600"><?= $widgetData['total'] ?></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Новые</span>
                                            <span class="font-medium text-orange-600"><?= $widgetData['new_orders'] ?></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">В работе</span>
                                            <span class="font-medium text-yellow-600"><?= $widgetData['in_progress'] ?></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Завершено</span>
                                            <span class="font-medium text-green-600"><?= $widgetData['completed'] ?></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Сегодня</span>
                                            <span class="font-medium text-blue-600"><?= $widgetData['today_orders'] ?></span>
                                        </div>
                                    </div>
                                </div>
                            
                            <?php elseif ($widgetType === 'recent_orders'): ?>
                                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 lg:col-span-2">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Последние заказы</h3>
                                    <div class="space-y-3">
                                        <?php foreach ($widgetData as $order): ?>
                                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                                <div>
                                                    <p class="font-medium text-gray-900">#<?= $order['id'] ?> - <?= $order['contact_name'] ?></p>
                                                    <p class="text-sm text-gray-600"><?= $order['pickup_address'] ?> → <?= $order['delivery_address'] ?></p>
                                                </div>
                                                <div class="text-right">
                                                    <span class="px-2 py-1 text-xs rounded-full 
                                                        <?= $order['status'] === 'new' ? 'bg-orange-100 text-orange-600' : 
                                                           ($order['status'] === 'completed' ? 'bg-green-100 text-green-600' : 'bg-yellow-100 text-yellow-600') ?>">
                                                        <?= ucfirst($order['status']) ?>
                                                    </span>
                                                    <p class="text-xs text-gray-500 mt-1"><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            
                            <?php elseif ($widgetType === 'carriers_stats'): ?>
                                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Ресурсы</h3>
                                    <div class="space-y-3">
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Активные перевозчики</span>
                                            <span class="font-medium text-purple-600"><?= $widgetData['active_carriers'] ?></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Свободный транспорт</span>
                                            <span class="font-medium text-green-600"><?= $widgetData['available_vehicles'] ?></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Доступные водители</span>
                                            <span class="font-medium text-blue-600"><?= $widgetData['available_drivers'] ?></span>
                                        </div>
                                    </div>
                                </div>
                            
                            <?php elseif ($widgetType === 'quick_actions'): ?>
                                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Быстрые действия</h3>
                                    <div class="grid grid-cols-1 gap-2">
                                        <?php foreach ($widgetData as $action): ?>
                                            <a href="<?= $action['url'] ?>" 
                                               class="flex items-center p-3 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                                                <i class="fas fa-<?= $action['icon'] ?> text-gray-500 mr-3"></i>
                                                <?= $action['name'] ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            
                            <?php elseif ($widgetType === 'system_status'): ?>
                                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Состояние системы</h3>
                                    <div class="space-y-3">
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">Статус</span>
                                            <span class="px-2 py-1 text-xs bg-green-100 text-green-600 rounded-full">
                                                <?= ucfirst($widgetData['status']) ?>
                                            </span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">PHP версия</span>
                                            <span class="font-medium text-gray-900"><?= $widgetData['php_version'] ?></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600">База данных</span>
                                            <span class="px-2 py-1 text-xs bg-blue-100 text-blue-600 rounded-full">
                                                <?= ucfirst($widgetData['database_status']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            
                            <?php elseif ($widgetType === 'revenue_chart'): ?>
                                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 lg:col-span-2">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Доходы за 7 дней</h3>
                                    <div class="space-y-2">
                                        <?php foreach ($widgetData as $day): ?>
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm text-gray-600"><?= date('d.m.Y', strtotime($day['date'])) ?></span>
                                                <div class="flex items-center">
                                                    <span class="text-sm font-medium text-gray-900 mr-2"><?= $day['orders_count'] ?> заказов</span>
                                                    <span class="text-sm text-green-600"><?= number_format($day['revenue'], 0) ?> ₸</span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <!-- Быстрые действия -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <!-- Статистика заказов -->
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Статистика заказов</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Новые</span>
                                <span class="font-medium text-blue-600"><?= $newOrders ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">В работе</span>
                                <span class="font-medium text-yellow-600"><?= $inProgressOrders ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Завершенные</span>
                                <span class="font-medium text-green-600"><?= $completedOrders ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Ресурсы -->
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Ресурсы</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Водители</span>
                                <span class="font-medium"><?= $availableDrivers ?>/<?= $totalDrivers ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Транспорт</span>
                                <span class="font-medium"><?= $availableVehicles ?>/<?= $totalVehicles ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Перевозчики</span>
                                <span class="font-medium"><?= $activeCarriers ?>/<?= $totalCarriers ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Быстрые действия -->
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Быстрые действия</h3>
                        <div class="space-y-2">
                            <?php if (CRMAuth::can('orders', 'create')): ?>
                            <a href="/astana.php" class="block w-full bg-blue-600 text-white text-center py-2 px-4 rounded hover:bg-blue-700 transition-colors">
                                <i class="fas fa-plus mr-2"></i>
                                Новый заказ
                            </a>
                            <?php endif; ?>
                            
                            <?php if (CRMAuth::can('orders', 'read')): ?>
                            <a href="/admin/crm_orders.php?status=new" class="block w-full bg-green-600 text-white text-center py-2 px-4 rounded hover:bg-green-700 transition-colors">
                                <i class="fas fa-eye mr-2"></i>
                                Новые заказы
                            </a>
                            <?php endif; ?>
                            
                            <?php if (CRMAuth::can('reports', 'read')): ?>
                            <a href="/admin/reports.php" class="block w-full bg-gray-600 text-white text-center py-2 px-4 rounded hover:bg-gray-700 transition-colors">
                                <i class="fas fa-chart-bar mr-2"></i>
                                Отчеты
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Последние заказы -->
                <?php if (CRMAuth::can('orders', 'read')): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-900">Последние заказы</h3>
                            <a href="/admin/crm_orders.php" class="text-blue-600 hover:text-blue-800">
                                Все заказы <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Тип</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Контакт</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Груз</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Дата</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($recentOrders as $order): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">#<?= $order['id'] ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $order['order_type'] === 'astana' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' ?>">
                                            <?= $order['order_type'] === 'astana' ? 'Астана' : 'Региональный' ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($order['contact_name']) ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($order['cargo_type']) ?></td>
                                    <td class="px-6 py-4 text-sm">
                                        <?php
                                        $statusColors = [
                                            'new' => 'bg-blue-100 text-blue-800',
                                            'assigned' => 'bg-yellow-100 text-yellow-800',
                                            'picked_up' => 'bg-purple-100 text-purple-800',
                                            'in_transit' => 'bg-orange-100 text-orange-800',
                                            'delivered' => 'bg-green-100 text-green-800'
                                        ];
                                        $statusText = [
                                            'new' => 'Новый',
                                            'assigned' => 'Назначен',
                                            'picked_up' => 'Забран',
                                            'in_transit' => 'В пути',
                                            'delivered' => 'Доставлен'
                                        ];
                                        ?>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-800' ?>">
                                            <?= $statusText[$order['status']] ?? 'Неизвестно' ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>