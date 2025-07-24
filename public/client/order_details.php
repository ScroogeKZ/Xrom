<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/database.php';

use App\Models\ShipmentOrder;
use App\ClientAuth;

// Проверяем авторизацию
ClientAuth::requireLogin();

$clientPhone = ClientAuth::getClientPhone();
$orderId = $_GET['id'] ?? null;

if (!$orderId) {
    header('Location: /client/dashboard.php');
    exit;
}

// Получаем детали заказа с CRM данными
try {
    $shipmentOrder = new ShipmentOrder();
    $orders = $shipmentOrder->getByClientPhone($clientPhone);
    
    // Находим нужный заказ и проверяем права доступа
    $order = null;
    foreach ($orders as $o) {
        if ($o['id'] == $orderId) {
            $order = $o;
            break;
        }
    }
    
    if (!$order) {
        header('Location: /client/dashboard.php');
        exit;
    }
    
} catch (Exception $e) {
    $error = "Ошибка загрузки заказа: " . $e->getMessage();
}

// Получаем расширенные статусы
$statusHistory = [
    'new' => ['name' => 'Заказ принят', 'completed' => true],
    'confirmed' => ['name' => 'Подтвержден', 'completed' => false],
    'assigned' => ['name' => 'Назначен водитель', 'completed' => false],
    'picked_up' => ['name' => 'Забран у отправителя', 'completed' => false],
    'in_transit' => ['name' => 'В пути', 'completed' => false],
    'delivered' => ['name' => 'Доставлен', 'completed' => false],
];

// Определяем какие статусы завершены
$currentStatus = $order['status'] ?? 'new';
$statusOrder = array_keys($statusHistory);
$currentIndex = array_search($currentStatus, $statusOrder);

for ($i = 0; $i <= $currentIndex; $i++) {
    $statusHistory[$statusOrder[$i]]['completed'] = true;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказ №<?= $order['id'] ?> - Хром-KZ</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <a href="/client/dashboard.php" class="text-gray-600 hover:text-gray-900">← Назад</a>
                    <div class="border-l border-gray-300 pl-3">
                        <h1 class="text-lg font-medium text-gray-900">Заказ №<?= $order['id'] ?></h1>
                        <p class="text-sm text-gray-600">Детальная информация</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/" class="text-gray-600 hover:text-gray-900">На главную</a>
                    <a href="/client/logout.php" class="bg-gray-600 text-white px-4 py-2 text-sm hover:bg-gray-700">Выйти</a>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (isset($error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 mb-6 rounded">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Статус заказа -->
        <div class="bg-white border border-gray-200 rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-medium text-gray-900">Статус доставки</h2>
                    <span class="px-3 py-1 text-sm font-medium rounded <?php
                        echo match($currentStatus) {
                            'new', 'confirmed' => 'bg-blue-100 text-blue-800',
                            'assigned', 'picked_up', 'in_transit' => 'bg-yellow-100 text-yellow-800',
                            'delivered' => 'bg-green-100 text-green-800',
                            'cancelled' => 'bg-red-100 text-red-800',
                            default => 'bg-gray-100 text-gray-800'
                        };
                    ?>">
                        <?= htmlspecialchars(ShipmentOrder::getStatusName($currentStatus)) ?>
                    </span>
                </div>
            </div>
            
            <!-- Прогресс трекинг -->
            <div class="px-6 py-6">
                <div class="space-y-4">
                    <?php foreach ($statusHistory as $status => $info): ?>
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <?php if ($info['completed']): ?>
                                    <div class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                <?php else: ?>
                                    <div class="w-6 h-6 bg-gray-300 rounded-full"></div>
                                <?php endif; ?>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium <?= $info['completed'] ? 'text-gray-900' : 'text-gray-500' ?>">
                                    <?= $info['name'] ?>
                                </p>
                                <?php if ($status === $currentStatus && $order['status_updated_at']): ?>
                                    <p class="text-xs text-gray-500">
                                        <?= date('d.m.Y H:i', strtotime($order['status_updated_at'])) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Информация о заказе -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Детали заказа -->
            <div class="bg-white border border-gray-200 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Детали заказа</h3>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <p class="text-sm font-medium text-gray-700">Откуда</p>
                        <p class="text-gray-900"><?= htmlspecialchars($order['pickup_address']) ?></p>
                        <?php if ($order['pickup_city']): ?>
                            <p class="text-gray-500 text-sm"><?= htmlspecialchars($order['pickup_city']) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($order['delivery_address']): ?>
                        <div>
                            <p class="text-sm font-medium text-gray-700">Куда</p>
                            <p class="text-gray-900"><?= htmlspecialchars($order['delivery_address']) ?></p>
                            <?php if ($order['destination_city']): ?>
                                <p class="text-gray-500 text-sm"><?= htmlspecialchars($order['destination_city']) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div>
                        <p class="text-sm font-medium text-gray-700">Тип груза</p>
                        <p class="text-gray-900"><?= htmlspecialchars($order['cargo_type']) ?></p>
                    </div>
                    
                    <?php if ($order['weight']): ?>
                        <div>
                            <p class="text-sm font-medium text-gray-700">Вес</p>
                            <p class="text-gray-900"><?= $order['weight'] ?> кг</p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($order['dimensions']): ?>
                        <div>
                            <p class="text-sm font-medium text-gray-700">Размеры</p>
                            <p class="text-gray-900"><?= htmlspecialchars($order['dimensions']) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div>
                        <p class="text-sm font-medium text-gray-700">Время готовности</p>
                        <p class="text-gray-900"><?= htmlspecialchars($order['ready_time']) ?></p>
                    </div>
                    
                    <div>
                        <p class="text-sm font-medium text-gray-700">Дата создания</p>
                        <p class="text-gray-900"><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></p>
                    </div>
                </div>
            </div>

            <!-- CRM Информация о доставке -->
            <div class="bg-white border border-gray-200 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Информация о доставке</h3>
                </div>
                <div class="px-6 py-4 space-y-6">
                    <?php if ($order['carrier_name']): ?>
                        <div>
                            <p class="text-sm font-medium text-gray-700 mb-2">Перевозчик</p>
                            <div class="bg-blue-50 p-4 rounded border border-blue-200">
                                <p class="font-medium text-blue-900"><?= htmlspecialchars($order['carrier_name']) ?></p>
                                <?php if ($order['carrier_phone']): ?>
                                    <p class="text-blue-700 text-sm mt-1">
                                        📞 <a href="tel:<?= $order['carrier_phone'] ?>" class="hover:underline"><?= htmlspecialchars($order['carrier_phone']) ?></a>
                                    </p>
                                <?php endif; ?>
                                <?php if ($order['carrier_license']): ?>
                                    <p class="text-blue-600 text-sm">Лицензия: <?= htmlspecialchars($order['carrier_license']) ?></p>
                                <?php endif; ?>
                                <?php if ($order['rating']): ?>
                                    <div class="flex items-center mt-2">
                                        <span class="text-yellow-500">★</span>
                                        <span class="text-blue-700 text-sm ml-1"><?= $order['rating'] ?>/5</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($order['driver_name']): ?>
                        <div>
                            <p class="text-sm font-medium text-gray-700 mb-2">Водитель</p>
                            <div class="bg-green-50 p-4 rounded border border-green-200">
                                <p class="font-medium text-green-900"><?= htmlspecialchars($order['driver_name']) ?></p>
                                <?php if ($order['driver_phone']): ?>
                                    <p class="text-green-700 text-sm mt-1">
                                        📞 <a href="tel:<?= $order['driver_phone'] ?>" class="hover:underline"><?= htmlspecialchars($order['driver_phone']) ?></a>
                                    </p>
                                <?php endif; ?>
                                <?php if ($order['driver_license']): ?>
                                    <p class="text-green-600 text-sm">Права: <?= htmlspecialchars($order['driver_license']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($order['brand'] || $order['license_plate']): ?>
                        <div>
                            <p class="text-sm font-medium text-gray-700 mb-2">Транспортное средство</p>
                            <div class="bg-gray-50 p-4 rounded border border-gray-200">
                                <?php if ($order['brand']): ?>
                                    <p class="font-medium text-gray-900"><?= htmlspecialchars($order['brand']) ?> <?= htmlspecialchars($order['model'] ?? '') ?></p>
                                    <?php if ($order['year']): ?>
                                        <p class="text-gray-600 text-sm"><?= $order['year'] ?> г.</p>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if ($order['license_plate']): ?>
                                    <p class="text-gray-700 font-mono text-lg mt-2"><?= htmlspecialchars($order['license_plate']) ?></p>
                                <?php endif; ?>
                                <?php if ($order['vehicle_type']): ?>
                                    <p class="text-gray-600 text-sm"><?= htmlspecialchars($order['vehicle_type']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!$order['carrier_name'] && !$order['driver_name'] && !$order['brand']): ?>
                        <div class="text-center py-8">
                            <div class="text-gray-400 mb-3">
                                <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <p class="text-gray-600">Информация о доставке будет доступна после назначения перевозчика</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Дополнительная информация -->
        <?php if ($order['notes'] || $order['comment']): ?>
            <div class="mt-6 bg-white border border-gray-200 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Дополнительная информация</h3>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <?php if ($order['notes']): ?>
                        <div>
                            <p class="text-sm font-medium text-gray-700">Примечания</p>
                            <p class="text-gray-900 mt-1"><?= htmlspecialchars($order['notes']) ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if ($order['comment']): ?>
                        <div>
                            <p class="text-sm font-medium text-gray-700">Комментарий</p>
                            <p class="text-gray-900 mt-1"><?= htmlspecialchars($order['comment']) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Действия -->
        <div class="mt-6 flex justify-between items-center">
            <a href="/client/dashboard.php" class="bg-gray-600 text-white px-6 py-2 hover:bg-gray-700">
                ← К списку заказов
            </a>
            
            <div class="flex space-x-3">
                <a href="/tracking.php?order=<?= $order['id'] ?>" class="bg-blue-600 text-white px-6 py-2 hover:bg-blue-700">
                    Отследить на карте
                </a>
                
                <?php if ($order['status'] === 'new'): ?>
                    <button onclick="cancelOrder(<?= $order['id'] ?>)" class="bg-red-600 text-white px-6 py-2 hover:bg-red-700">
                        Отменить заказ
                    </button>
                <?php endif; ?>
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