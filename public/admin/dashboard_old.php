<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\ShipmentOrder;
use App\Models\User;
use App\Auth;

Auth::requireAuth();

$orderModel = new ShipmentOrder();
$userModel = new User();

// Get statistics
$stats = [
    'total_orders' => $orderModel->getCount(),
    'astana_orders' => $orderModel->getCount(['order_type' => 'astana']),
    'regional_orders' => $orderModel->getCount(['order_type' => 'regional']),
    'new_orders' => $orderModel->getCount(['status' => 'new']),
    'processing_orders' => $orderModel->getCount(['status' => 'processing']),
    'completed_orders' => $orderModel->getCount(['status' => 'completed'])
];

// Get recent orders
$recentOrders = $orderModel->getAll(['limit' => 10]);

// Get current user
$currentUser = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления - Хром-KZ Логистика</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-hover {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <div class="gradient-bg p-2 rounded-lg">
                        <img src="/assets/logo.png" alt="Хром-KZ" class="h-8 w-8 filter brightness-0 invert" onerror="this.style.display='none'">
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800">Хром-KZ Админ</h1>
                        <p class="text-sm text-gray-600">Панель управления</p>
                    </div>
                </div>
                <div class="flex items-center space-x-6">
                    <span class="text-sm text-gray-600">Добро пожаловать, <?php echo htmlspecialchars($currentUser['username'] ?? 'Администратор'); ?></span>
                    <div class="flex space-x-4">
                        <a href="/" class="text-gray-600 hover:text-blue-600 transition-colors">Главная</a>
                        <a href="/admin/panel.php" class="text-gray-600 hover:text-blue-600 transition-colors">Заказы</a>
                        <a href="/admin/users.php" class="text-gray-600 hover:text-blue-600 transition-colors">Пользователи</a>
                        <a href="/admin/logout.php" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition-colors">Выйти</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Дашборд</h1>
            <p class="text-gray-600 mt-2">Обзор системы управления заказами</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <span class="text-2xl">📦</span>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Всего заказов</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_orders']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <span class="text-2xl">🚚</span>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Заказы по Астане</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['astana_orders']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <span class="text-2xl">🌍</span>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Межгородские</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['regional_orders']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <span class="text-2xl">⏳</span>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Новые заказы</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['new_orders']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Chart -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Статистика заказов</h3>
                <canvas id="ordersChart" width="400" height="200"></canvas>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Быстрые действия</h3>
                <div class="space-y-4">
                    <a href="/astana.php" class="block w-full bg-blue-600 text-white text-center py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors">
                        📋 Создать заказ по Астане
                    </a>
                    <a href="/regional.php" class="block w-full bg-green-600 text-white text-center py-3 px-4 rounded-lg hover:bg-green-700 transition-colors">
                        🗂️ Создать межгородской заказ
                    </a>
                    <a href="/admin/panel.php" class="block w-full bg-purple-600 text-white text-center py-3 px-4 rounded-lg hover:bg-purple-700 transition-colors">
                        📊 Управление заказами
                    </a>
                    <a href="/admin/users.php" class="block w-full bg-orange-600 text-white text-center py-3 px-4 rounded-lg hover:bg-orange-700 transition-colors">
                        👥 Управление пользователями
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">Последние заказы</h3>
                    <a href="/admin/panel.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Посмотреть все</a>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Тип</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Контакт</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Дата</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach (array_slice($recentOrders, 0, 5) as $order): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    #<?php echo $order['id']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $order['order_type'] === 'astana' ? '🚚 Астана' : '🌍 Межгород'; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?php echo htmlspecialchars($order['contact_name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php 
                                        echo match($order['status']) {
                                            'new' => 'bg-blue-100 text-blue-800',
                                            'processing' => 'bg-yellow-100 text-yellow-800',
                                            'completed' => 'bg-green-100 text-green-800',
                                            default => 'bg-gray-100 text-gray-800'
                                        };
                                        ?>">
                                        <?php 
                                        echo match($order['status']) {
                                            'new' => 'Новый',
                                            'processing' => 'В обработке',
                                            'completed' => 'Завершен',
                                            default => $order['status']
                                        };
                                        ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Orders Chart
        const ctx = document.getElementById('ordersChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Новые', 'В обработке', 'Завершенные'],
                datasets: [{
                    data: [<?php echo $stats['new_orders']; ?>, <?php echo $stats['processing_orders']; ?>, <?php echo $stats['completed_orders']; ?>],
                    backgroundColor: [
                        '#3B82F6',
                        '#F59E0B',
                        '#10B981'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });

        // Auto-refresh page every 30 seconds
        setTimeout(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>