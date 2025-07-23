<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['client_id'])) {
    header('Location: /client/login.php');
    exit;
}

$db = \Database::getInstance()->getConnection();

// Получаем заказы клиента
$stmt = $db->prepare("
    SELECT so.*, 
           CASE 
               WHEN so.status = 'new' THEN 'Новый'
               WHEN so.status = 'processing' THEN 'В обработке'
               WHEN so.status = 'completed' THEN 'Завершен'
               ELSE so.status
           END as status_text
    FROM shipment_orders so 
    WHERE so.contact_phone = (SELECT phone FROM clients WHERE id = ?) 
    ORDER BY so.created_at DESC
");
$stmt->execute([$_SESSION['client_id']]);
$orders = $stmt->fetchAll();

// Статистика клиента
$stats = [
    'total' => count($orders),
    'new' => count(array_filter($orders, fn($o) => $o['status'] === 'new')),
    'processing' => count(array_filter($orders, fn($o) => $o['status'] === 'processing')),
    'completed' => count(array_filter($orders, fn($o) => $o['status'] === 'completed'))
];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - Хром-KZ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <img src="/assets/logo.png" alt="Хром-KZ" class="h-6 w-6" onerror="this.style.display='none'">
                    <span class="text-lg font-medium text-gray-900">Хром-KZ</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">Добро пожаловать, <?= htmlspecialchars($_SESSION['client_name']) ?></span>
                    <a href="/client/logout.php" class="text-red-600 hover:text-red-700">Выход</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 border border-gray-200">
                <div class="text-3xl font-bold text-blue-600"><?= $stats['total'] ?></div>
                <div class="text-sm text-gray-600 mt-1">Всего заказов</div>
            </div>
            <div class="bg-white p-6 border border-gray-200">
                <div class="text-3xl font-bold text-yellow-600"><?= $stats['new'] ?></div>
                <div class="text-sm text-gray-600 mt-1">Новые</div>
            </div>
            <div class="bg-white p-6 border border-gray-200">
                <div class="text-3xl font-bold text-orange-600"><?= $stats['processing'] ?></div>
                <div class="text-sm text-gray-600 mt-1">В обработке</div>
            </div>
            <div class="bg-white p-6 border border-gray-200">
                <div class="text-3xl font-bold text-green-600"><?= $stats['completed'] ?></div>
                <div class="text-sm text-gray-600 mt-1">Завершено</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mb-8">
            <div class="bg-white p-6 border border-gray-200">
                <h2 class="text-lg font-medium mb-4">Быстрые действия</h2>
                <div class="flex space-x-4">
                    <a href="/astana.php" class="bg-blue-600 text-white px-4 py-2 hover:bg-blue-700">
                        Новый заказ по Астане
                    </a>
                    <a href="/regional.php" class="bg-green-600 text-white px-4 py-2 hover:bg-green-700">
                        Межгородский заказ
                    </a>
                    <button onclick="calculateCost()" class="bg-gray-600 text-white px-4 py-2 hover:bg-gray-700">
                        Калькулятор стоимости
                    </button>
                </div>
            </div>
        </div>

        <!-- Orders List -->
        <div class="bg-white border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium">Мои заказы</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Заказ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Тип</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Груз</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Адрес забора</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Дата</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($orders as $order): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">#<?= $order['id'] ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= $order['order_type'] === 'astana' ? 'Астана' : 'Межгород' ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= htmlspecialchars($order['cargo_type']) ?>
                                <?php if ($order['weight']): ?>
                                    <br><span class="text-xs text-gray-500"><?= $order['weight'] ?> кг</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= htmlspecialchars($order['pickup_address']) ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                    <?php if ($order['status'] === 'new'): ?>bg-yellow-100 text-yellow-800
                                    <?php elseif ($order['status'] === 'processing'): ?>bg-blue-100 text-blue-800
                                    <?php elseif ($order['status'] === 'completed'): ?>bg-green-100 text-green-800
                                    <?php else: ?>bg-gray-100 text-gray-800<?php endif; ?>">
                                    <?= $order['status_text'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <button onclick="showOrderDetails(<?= $order['id'] ?>)" 
                                        class="text-blue-600 hover:text-blue-700">
                                    Подробнее
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                У вас пока нет заказов
                                <div class="mt-2">
                                    <a href="/astana.php" class="text-blue-600 hover:text-blue-700">Создать первый заказ</a>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div id="orderModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white max-w-2xl w-full p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-medium">Детали заказа</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="orderDetails">
                    <!-- Order details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        function showOrderDetails(orderId) {
            document.getElementById('orderModal').classList.remove('hidden');
            document.getElementById('orderDetails').innerHTML = '<div class="text-center py-4">Загрузка...</div>';
            
            // Here you would make an AJAX request to get order details
            // For now, we'll show a placeholder
            setTimeout(() => {
                document.getElementById('orderDetails').innerHTML = `
                    <div class="space-y-4">
                        <div><strong>Заказ:</strong> #${orderId}</div>
                        <div><strong>Статус:</strong> В обработке</div>
                        <div><strong>Трекинг:</strong> Заказ принят в обработку</div>
                        <div class="mt-6">
                            <div class="text-sm font-medium text-gray-700 mb-2">История статусов:</div>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span>Заказ создан</span>
                                    <span class="text-gray-500">${new Date().toLocaleDateString('ru-RU')}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Принят в обработку</span>
                                    <span class="text-gray-500">${new Date().toLocaleDateString('ru-RU')}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }, 500);
        }
        
        function closeModal() {
            document.getElementById('orderModal').classList.add('hidden');
        }
        
        function calculateCost() {
            alert('Калькулятор стоимости доставки (в разработке)');
        }
    </script>
</body>
</html>