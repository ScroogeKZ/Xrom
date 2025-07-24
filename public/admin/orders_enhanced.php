<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;
use App\Models\ShipmentOrder;
use App\Models\Driver;

// Проверка авторизации
if (!Auth::isAuthenticated()) {
    header('Location: /admin/login.php');
    exit;
}

$orderModel = new ShipmentOrder();
$driverModel = new Driver();

// Получение данных
$filters = [];
if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
if (!empty($_GET['order_type'])) $filters['order_type'] = $_GET['order_type'];
if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];

$orders = $orderModel->getAll($filters);
$drivers = $driverModel->getAll();
$availableDrivers = $driverModel->getAvailableDrivers();

// Обработка сообщений
$message = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление заказами - Хром-KZ Админ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-new { background: #fef3c7; color: #92400e; }
        .status-confirmed { background: #dbeafe; color: #1e40af; }
        .status-assigned { background: #e0e7ff; color: #3730a3; }
        .status-picked_up { background: #fde68a; color: #92400e; }
        .status-in_transit { background: #bfdbfe; color: #1d4ed8; }
        .status-at_destination { background: #c7d2fe; color: #3730a3; }
        .status-out_for_delivery { background: #fed7aa; color: #ea580c; }
        .status-delivered { background: #bbf7d0; color: #047857; }
        .status-failed_delivery { background: #fecaca; color: #dc2626; }
        .status-returned { background: #fde68a; color: #d97706; }
        .status-cancelled { background: #f3f4f6; color: #374151; }
        .status-on_hold { background: #e5e7eb; color: #6b7280; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Навигация -->
    <div class="bg-white shadow-sm mb-6">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-800">Управление заказами</h1>
                <nav class="flex space-x-4">
                    <a href="/admin/panel.php" class="text-gray-600 hover:text-gray-800">Главная</a>
                    <a href="/admin/orders_enhanced.php" class="text-gray-800 font-medium">Заказы</a>
                    <a href="/admin/drivers.php" class="text-gray-600 hover:text-gray-800">Водители</a>
                    <a href="/admin/reports.php" class="text-gray-600 hover:text-gray-800">Отчеты</a>
                    <a href="/admin/logout.php" class="text-red-600 hover:text-red-800">Выход</a>
                </nav>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4">
        <!-- Уведомления -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 bg-green-50 text-green-800 border border-green-200 rounded-lg">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 text-red-800 border border-red-200 rounded-lg">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Статистика -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <?php
            $stats = [
                'new' => count(array_filter($orders, fn($o) => $o['status'] === 'new')),
                'in_progress' => count(array_filter($orders, fn($o) => in_array($o['status'], ['confirmed', 'assigned', 'picked_up', 'in_transit']))),
                'delivered' => count(array_filter($orders, fn($o) => $o['status'] === 'delivered')),
                'total' => count($orders)
            ];
            ?>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Новые заказы</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['new']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">В работе</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['in_progress']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Доставлено</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['delivered']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-gray-100 rounded-lg">
                        <svg class="w-6 h-6 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 2V6a1 1 0 112 0v1a1 1 0 11-2 0zm3 0V6a1 1 0 112 0v1a1 1 0 11-2 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Всего заказов</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Фильтры -->
        <div class="bg-white rounded-lg shadow mb-6 p-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Статус</label>
                    <select name="status" class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                        <option value="">Все статусы</option>
                        <?php foreach (ShipmentOrder::getStatuses() as $key => $name): ?>
                            <option value="<?php echo $key; ?>" <?php echo ($_GET['status'] ?? '') === $key ? 'selected' : ''; ?>>
                                <?php echo $name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Тип заказа</label>
                    <select name="order_type" class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                        <option value="">Все типы</option>
                        <option value="astana" <?php echo ($_GET['order_type'] ?? '') === 'astana' ? 'selected' : ''; ?>>Астана</option>
                        <option value="regional" <?php echo ($_GET['order_type'] ?? '') === 'regional' ? 'selected' : ''; ?>>Региональные</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Поиск</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" 
                           placeholder="Имя или телефон" class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">
                        Применить фильтры
                    </button>
                </div>
            </form>
        </div>

        <!-- Таблица заказов -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Тип</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Клиент</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Адрес забора</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Водитель</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Создан</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($orders as $order): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">#<?php echo $order['id']; ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    <?php echo $order['order_type'] === 'astana' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'; ?>">
                                    <?php echo $order['order_type'] === 'astana' ? 'Астана' : 'Региональный'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div>
                                    <div class="font-medium"><?php echo htmlspecialchars($order['contact_name']); ?></div>
                                    <div class="text-gray-500"><?php echo htmlspecialchars($order['contact_phone']); ?></div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php echo htmlspecialchars(substr($order['pickup_address'], 0, 50)) . (strlen($order['pickup_address']) > 50 ? '...' : ''); ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php echo ShipmentOrder::getStatusName($order['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php if ($order['driver_name']): ?>
                                    <div class="font-medium text-green-600"><?php echo htmlspecialchars($order['driver_name']); ?></div>
                                <?php else: ?>
                                    <span class="text-gray-400">Не назначен</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <button onclick="openStatusModal(<?php echo htmlspecialchars(json_encode($order)); ?>)" 
                                        class="text-blue-600 hover:text-blue-800 mr-3">Изменить статус</button>
                                <button onclick="openDetailsModal(<?php echo htmlspecialchars(json_encode($order)); ?>)" 
                                        class="text-gray-600 hover:text-gray-800">Детали</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Модальное окно изменения статуса -->
    <div id="statusModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <h3 class="text-lg font-semibold mb-4">Изменить статус заказа</h3>
            <form action="/admin/order_status.php" method="POST">
                <input type="hidden" name="order_id" id="modalOrderId">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Новый статус</label>
                        <select name="status" id="modalStatus" required class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                            <?php foreach (ShipmentOrder::getStatuses() as $key => $name): ?>
                                <option value="<?php echo $key; ?>"><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Назначить водителя</label>
                        <select name="driver_id" id="modalDriver" class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                            <option value="">Без водителя</option>
                            <option value="unassign">Снять назначение</option>
                            <?php foreach ($availableDrivers as $driver): ?>
                                <option value="<?php echo $driver['id']; ?>">
                                    <?php echo htmlspecialchars($driver['name']) . ' (' . htmlspecialchars($driver['vehicle_type']) . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-4 mt-6">
                    <button type="button" onclick="closeStatusModal()" 
                            class="px-4 py-2 text-gray-600 border rounded hover:bg-gray-50">Отмена</button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модальное окно деталей заказа -->
    <div id="detailsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-4 max-h-screen overflow-y-auto">
            <h3 class="text-lg font-semibold mb-4">Детали заказа</h3>
            <div id="orderDetails" class="space-y-4">
                <!-- Содержимое будет заполнено JavaScript -->
            </div>
            <div class="flex justify-end mt-6">
                <button onclick="closeDetailsModal()" 
                        class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">Закрыть</button>
            </div>
        </div>
    </div>

    <script>
        function openStatusModal(order) {
            document.getElementById('modalOrderId').value = order.id;
            document.getElementById('modalStatus').value = order.status;
            
            // Установить текущего водителя, если назначен
            const driverSelect = document.getElementById('modalDriver');
            if (order.driver_id) {
                driverSelect.value = order.driver_id;
            } else {
                driverSelect.value = '';
            }
            
            document.getElementById('statusModal').classList.remove('hidden');
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
        }

        function openDetailsModal(order) {
            const detailsDiv = document.getElementById('orderDetails');
            const statuses = <?php echo json_encode(ShipmentOrder::getStatuses()); ?>;
            
            detailsDiv.innerHTML = `
                <div class="grid grid-cols-2 gap-4">
                    <div><strong>ID заказа:</strong> #${order.id}</div>
                    <div><strong>Тип:</strong> ${order.order_type === 'astana' ? 'Астана' : 'Региональный'}</div>
                    <div><strong>Статус:</strong> ${statuses[order.status] || order.status}</div>
                    <div><strong>Водитель:</strong> ${order.driver_name || 'Не назначен'}</div>
                    <div><strong>Клиент:</strong> ${order.contact_name}</div>
                    <div><strong>Телефон:</strong> ${order.contact_phone}</div>
                    <div class="col-span-2"><strong>Адрес забора:</strong> ${order.pickup_address}</div>
                    ${order.delivery_address ? `<div class="col-span-2"><strong>Адрес доставки:</strong> ${order.delivery_address}</div>` : ''}
                    <div><strong>Время готовности:</strong> ${order.ready_time || 'Не указано'}</div>
                    <div><strong>Тип груза:</strong> ${order.cargo_type || 'Не указан'}</div>
                    <div><strong>Вес:</strong> ${order.weight ? order.weight + ' кг' : 'Не указан'}</div>
                    <div><strong>Размеры:</strong> ${order.dimensions || 'Не указаны'}</div>
                    ${order.destination_city ? `<div><strong>Город назначения:</strong> ${order.destination_city}</div>` : ''}
                    ${order.delivery_method ? `<div><strong>Способ доставки:</strong> ${order.delivery_method}</div>` : ''}
                    <div><strong>Создан:</strong> ${new Date(order.created_at).toLocaleString('ru-RU')}</div>
                    <div><strong>Обновлен:</strong> ${new Date(order.updated_at).toLocaleString('ru-RU')}</div>
                    ${order.notes ? `<div class="col-span-2"><strong>Примечания:</strong> ${order.notes}</div>` : ''}
                    ${order.comment ? `<div class="col-span-2"><strong>Комментарий:</strong> ${order.comment}</div>` : ''}
                    ${order.shipping_cost ? `<div><strong>Стоимость:</strong> ${order.shipping_cost} ₸</div>` : ''}
                </div>
            `;
            
            document.getElementById('detailsModal').classList.remove('hidden');
        }

        function closeDetailsModal() {
            document.getElementById('detailsModal').classList.add('hidden');
        }

        // Закрытие модальных окон по клику вне их
        document.getElementById('statusModal').addEventListener('click', function(e) {
            if (e.target === this) closeStatusModal();
        });

        document.getElementById('detailsModal').addEventListener('click', function(e) {
            if (e.target === this) closeDetailsModal();
        });
    </script>
</body>
</html>