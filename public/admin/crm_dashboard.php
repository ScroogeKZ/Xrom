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
                <!-- Приветствие -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Добро пожаловать в CRM систему</h1>
                    <p class="text-gray-600">Обзор деятельности логистической компании Хром-KZ</p>
                </div>

                <!-- Основная статистика -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Заказы -->
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center">
                            <div class="p-3 bg-blue-100 rounded-full">
                                <i class="fas fa-box text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-2xl font-bold text-gray-900"><?= $totalOrders ?></h3>
                                <p class="text-gray-600">Всего заказов</p>
                            </div>
                        </div>
                    </div>

                    <!-- Водители -->
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center">
                            <div class="p-3 bg-green-100 rounded-full">
                                <i class="fas fa-user-tie text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-2xl font-bold text-gray-900"><?= $availableDrivers ?></h3>
                                <p class="text-gray-600">Доступно водителей</p>
                            </div>
                        </div>
                    </div>

                    <!-- Транспорт -->
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center">
                            <div class="p-3 bg-yellow-100 rounded-full">
                                <i class="fas fa-truck text-yellow-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-2xl font-bold text-gray-900"><?= $availableVehicles ?></h3>
                                <p class="text-gray-600">Свободный транспорт</p>
                            </div>
                        </div>
                    </div>

                    <!-- Перевозчики -->
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center">
                            <div class="p-3 bg-purple-100 rounded-full">
                                <i class="fas fa-building text-purple-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-2xl font-bold text-gray-900"><?= $activeCarriers ?></h3>
                                <p class="text-gray-600">Активных перевозчиков</p>
                            </div>
                        </div>
                    </div>
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