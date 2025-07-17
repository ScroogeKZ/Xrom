<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\ShipmentOrder;
use App\Models\User;
use App\Auth;
use App\TelegramService;

Auth::requireAuth();

$orderModel = new ShipmentOrder();
$userModel = new User();
$telegramService = new TelegramService();

// Основная статистика
$stats = [
    'total_orders' => $orderModel->getCount(),
    'astana_orders' => $orderModel->getCount(['order_type' => 'astana']),
    'regional_orders' => $orderModel->getCount(['order_type' => 'regional']),
    'new_orders' => $orderModel->getCount(['status' => 'new']),
    'processing_orders' => $orderModel->getCount(['status' => 'processing']),
    'completed_orders' => $orderModel->getCount(['status' => 'completed']),
    'cancelled_orders' => $orderModel->getCount(['status' => 'cancelled'])
];

// Временная статистика
$today = date('Y-m-d');
$weekAgo = date('Y-m-d', strtotime('-7 days'));
$monthAgo = date('Y-m-d', strtotime('-30 days'));

$timeStats = [
    'today_orders' => $orderModel->getCount(['date_from' => $today]),
    'week_orders' => $orderModel->getCount(['date_from' => $weekAgo]),
    'month_orders' => $orderModel->getCount(['date_from' => $monthAgo])
];

// Дополнительная статистика
$recentOrders = $orderModel->getAll(['limit' => 8]);
$urgentOrders = $orderModel->getAll(['status' => 'new', 'limit' => 5]);
$popularDestinations = $orderModel->getPopularDestinations(5);
$statusDistribution = $orderModel->getStatusDistribution();
$orderTypeDistribution = $orderModel->getOrderTypeDistribution();

// Информация о системе
$totalUsers = $userModel->getCount();
$telegramConfigured = $telegramService->isConfigured();

$currentUser = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Дашборд - Хром-KZ Логистика</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#1e40af',
                        'primary-dark': '#1e3a8a',
                        'secondary': '#f59e0b',
                        'accent': '#10b981'
                    }
                }
            }
        }
    </script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 50%, #3730a3 100%);
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
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white/95 backdrop-blur-sm shadow-xl sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <div class="gradient-bg p-2 rounded-lg">
                        <img src="/assets/logo.png" alt="Хром-KZ" class="h-8 w-8 filter brightness-0 invert" onerror="this.style.display='none'">
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold bg-gradient-to-r from-primary to-primary-dark bg-clip-text text-transparent">Хром-KZ Админ</h1>
                        <p class="text-sm text-gray-600 font-medium">Панель управления</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/" class="text-gray-600 hover:text-primary transition-colors">Главная</a>
                    <a href="/admin/orders.php" class="text-gray-600 hover:text-primary transition-colors">Заказы</a>
                    <a href="/admin/settings.php" class="text-gray-600 hover:text-primary transition-colors">Настройки</a>
                    <span class="text-gray-600">Добро пожаловать, <strong><?php echo htmlspecialchars($currentUser['username']); ?></strong></span>
                    <a href="/admin/logout.php" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">Выйти</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Заголовок дашборда -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">Панель управления</h1>
            <p class="text-gray-600">Обзор деятельности системы управления заказами</p>
        </div>

        <!-- Основные метрики -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Всего заказов -->
            <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Всего заказов</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $stats['total_orders']; ?></p>
                        <p class="text-sm text-green-600 mt-1">📦 Общее количество</p>
                    </div>
                    <div class="bg-blue-500 p-3 rounded-full">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Новые заказы -->
            <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Новые заказы</p>
                        <p class="text-3xl font-bold text-yellow-600"><?php echo $stats['new_orders']; ?></p>
                        <p class="text-sm text-yellow-600 mt-1">⚠️ Требуют внимания</p>
                    </div>
                    <div class="bg-yellow-500 p-3 rounded-full">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- В работе -->
            <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">В работе</p>
                        <p class="text-3xl font-bold text-blue-600"><?php echo $stats['processing_orders']; ?></p>
                        <p class="text-sm text-blue-600 mt-1">🔄 Активные заказы</p>
                    </div>
                    <div class="bg-blue-500 p-3 rounded-full">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Завершенные -->
            <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Завершенные</p>
                        <p class="text-3xl font-bold text-green-600"><?php echo $stats['completed_orders']; ?></p>
                        <p class="text-sm text-green-600 mt-1">✅ Успешно выполнены</p>
                    </div>
                    <div class="bg-green-500 p-3 rounded-full">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Временная статистика -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">📅 Сегодня</h3>
                <p class="text-2xl font-bold text-purple-600"><?php echo $timeStats['today_orders']; ?></p>
                <p class="text-sm text-gray-600">новых заказов</p>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">📊 За неделю</h3>
                <p class="text-2xl font-bold text-indigo-600"><?php echo $timeStats['week_orders']; ?></p>
                <p class="text-sm text-gray-600">заказов создано</p>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg p-6 card-hover">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">📈 За месяц</h3>
                <p class="text-2xl font-bold text-blue-600"><?php echo $timeStats['month_orders']; ?></p>
                <p class="text-sm text-gray-600">общий объем</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Типы заказов -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-6">Распределение по типам</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="w-4 h-4 bg-blue-500 rounded-full"></div>
                            <span class="font-medium text-gray-700">Заказы по Астане</span>
                        </div>
                        <span class="text-xl font-bold text-blue-600"><?php echo $stats['astana_orders']; ?></span>
                    </div>
                    
                    <div class="flex items-center justify-between p-4 bg-purple-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="w-4 h-4 bg-purple-500 rounded-full"></div>
                            <span class="font-medium text-gray-700">Региональные заказы</span>
                        </div>
                        <span class="text-xl font-bold text-purple-600"><?php echo $stats['regional_orders']; ?></span>
                    </div>
                    
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="w-4 h-4 bg-gray-400 rounded-full"></div>
                            <span class="font-medium text-gray-700">Отмененные заказы</span>
                        </div>
                        <span class="text-xl font-bold text-gray-600"><?php echo $stats['cancelled_orders']; ?></span>
                    </div>
                </div>
            </div>

            <!-- Популярные направления -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-6">🏆 Популярные направления</h3>
                <?php if (!empty($popularDestinations)): ?>
                    <div class="space-y-3">
                        <?php foreach ($popularDestinations as $index => $destination): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <span class="w-6 h-6 bg-gradient-to-r from-yellow-400 to-orange-500 text-white text-xs font-bold rounded-full flex items-center justify-center">
                                        <?php echo $index + 1; ?>
                                    </span>
                                    <span class="font-medium text-gray-700"><?php echo htmlspecialchars($destination['destination_city']); ?></span>
                                </div>
                                <span class="text-lg font-bold text-gray-600"><?php echo $destination['count']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-4">Нет данных о направлениях</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Последние заказы и срочные -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Последние заказы -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-800">📋 Последние заказы</h3>
                    <a href="/admin/orders.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Показать все →</a>
                </div>
                
                <?php if (!empty($recentOrders)): ?>
                    <div class="space-y-3">
                        <?php foreach (array_slice($recentOrders, 0, 5) as $order): ?>
                            <div class="p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer" onclick="window.location.href='/admin/edit_order.php?id=<?php echo $order['id']; ?>'">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-medium text-gray-900">#<?php echo $order['id']; ?> - <?php echo htmlspecialchars($order['contact_name'] ?? 'Не указано'); ?></p>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars(substr($order['cargo_type'] ?? '', 0, 40)); ?><?php echo strlen($order['cargo_type'] ?? '') > 40 ? '...' : ''; ?></p>
                                        <p class="text-xs text-gray-500"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></p>
                                    </div>
                                    <span class="px-2 py-1 text-xs rounded-full <?php
                                        $statusColors = [
                                            'new' => 'bg-yellow-100 text-yellow-800',
                                            'processing' => 'bg-blue-100 text-blue-800',
                                            'completed' => 'bg-green-100 text-green-800',
                                            'cancelled' => 'bg-red-100 text-red-800'
                                        ];
                                        echo $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>">
                                        <?php
                                            $statusTexts = [
                                                'new' => 'Новый',
                                                'processing' => 'В работе',
                                                'completed' => 'Завершен',
                                                'cancelled' => 'Отменен'
                                            ];
                                            echo $statusTexts[$order['status']] ?? 'Неизвестно';
                                        ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-4">Нет заказов</p>
                <?php endif; ?>
            </div>

            <!-- Срочные заказы -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-800">⚠️ Новые заказы</h3>
                    <a href="/admin/orders.php?status=new" class="text-red-600 hover:text-red-800 text-sm font-medium">Все новые →</a>
                </div>
                
                <?php if (!empty($urgentOrders)): ?>
                    <div class="space-y-3">
                        <?php foreach ($urgentOrders as $order): ?>
                            <div class="p-3 border-l-4 border-red-500 bg-red-50 rounded-r-lg hover:bg-red-100 transition-colors cursor-pointer" onclick="window.location.href='/admin/edit_order.php?id=<?php echo $order['id']; ?>'">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-medium text-gray-900">#<?php echo $order['id']; ?> - <?php echo htmlspecialchars($order['contact_name'] ?? 'Не указано'); ?></p>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($order['contact_phone'] ?? ''); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></p>
                                    </div>
                                    <span class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded-full">Новый</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <p class="text-green-600 font-medium">✅ Все заказы обработаны!</p>
                        <p class="text-sm text-gray-500">Нет новых заказов, требующих внимания</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Системная информация -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-6">🔧 Системная информация</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <div class="text-center p-4 bg-blue-50 rounded-lg">
                    <h4 class="font-semibold text-gray-800 mb-2">PHP Версия</h4>
                    <p class="text-lg font-mono text-blue-600"><?php echo PHP_VERSION; ?></p>
                </div>
                
                <div class="text-center p-4 bg-green-50 rounded-lg">
                    <h4 class="font-semibold text-gray-800 mb-2">База данных</h4>
                    <p class="text-lg text-green-600">PostgreSQL</p>
                </div>
                
                <div class="text-center p-4 bg-purple-50 rounded-lg">
                    <h4 class="font-semibold text-gray-800 mb-2">Пользователи</h4>
                    <p class="text-lg text-purple-600"><?php echo $totalUsers; ?> админов</p>
                </div>
                
                <div class="text-center p-4 <?php echo $telegramConfigured ? 'bg-green-50' : 'bg-yellow-50'; ?> rounded-lg">
                    <h4 class="font-semibold text-gray-800 mb-2">Telegram Bot</h4>
                    <p class="text-lg <?php echo $telegramConfigured ? 'text-green-600' : 'text-yellow-600'; ?>">
                        <?php echo $telegramConfigured ? '✅ Активен' : '⚠️ Не настроен'; ?>
                    </p>
                </div>
            </div>
            
            <div class="mt-6 flex flex-wrap gap-4">
                <a href="/admin/orders.php" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    Управление заказами
                </a>
                <a href="/admin/settings.php" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors">
                    Настройки системы
                </a>
                <a href="/" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                    Главная страница
                </a>
            </div>
        </div>
    </div>
</body>
</html>