<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\ShipmentOrder;
use App\Auth;

Auth::requireAuth();

$orderModel = new ShipmentOrder();
$telegramService = new App\TelegramService();
$filters = [];

// Handle filters
if (isset($_GET['date_from'])) $filters['date_from'] = $_GET['date_from'];
if (isset($_GET['date_to'])) $filters['date_to'] = $_GET['date_to'];
if (isset($_GET['order_type'])) $filters['order_type'] = $_GET['order_type'];
if (isset($_GET['status'])) $filters['status'] = $_GET['status'];

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['status'];
    
    try {
        $orderModel->updateStatus($orderId, $newStatus);
        $success = 'Статус заказа обновлен';
    } catch (Exception $e) {
        $error = 'Ошибка обновления: ' . $e->getMessage();
    }
}

$orders = $orderModel->getAll($filters);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления - Хром-KZ Логистика</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);" class="p-2 rounded-lg">
                        <img src="/assets/logo.png" alt="Хром-KZ" class="h-8 w-8 filter brightness-0 invert" onerror="this.style.display='none'">
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800">Хром-KZ Админ</h1>
                        <p class="text-sm text-gray-600">Управление заказами</p>
                    </div>
                </div>
                <div class="flex space-x-4">
                    <a href="/admin/dashboard.php" class="text-gray-600 hover:text-blue-600 transition-colors">Дашборд</a>
                    <a href="/admin/users.php" class="text-gray-600 hover:text-blue-600 transition-colors">Пользователи</a>
                    <a href="/" class="text-gray-600 hover:text-blue-600 transition-colors">Главная</a>
                    <a href="/admin/logout.php" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition-colors">Выйти</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Управление заказами</h1>
        
        <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Filters -->
        <!-- Telegram Status -->
        <div class="bg-white p-4 rounded-lg shadow-lg mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold">Статус Telegram уведомлений</h3>
                    <p class="text-sm text-gray-600">
                        <?php if ($telegramService->isConfigured()): ?>
                            <span class="text-green-600">✓ Telegram настроен и работает</span>
                        <?php else: ?>
                            <span class="text-red-600">✗ Telegram не настроен</span>
                        <?php endif; ?>
                    </p>
                </div>
                <?php if (!$telegramService->isConfigured()): ?>
                    <div class="text-sm text-gray-500">
                        <p>Для настройки уведомлений нужны:</p>
                        <p>• TELEGRAM_BOT_TOKEN</p>
                        <p>• TELEGRAM_CHAT_ID</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Enhanced Filters and Search -->
        <div class="bg-white p-6 rounded-xl shadow-md mb-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold">Поиск и фильтрация</h2>
                <div class="flex space-x-3">
                    <button onclick="toggleBulkActions()" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                        📋 Массовые действия
                    </button>
                    <button onclick="exportOrders()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                        📊 Экспорт
                    </button>
                </div>
            </div>
            
            <form method="GET" class="grid md:grid-cols-5 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">🔍 Поиск</label>
                    <input type="text" name="search" placeholder="Поиск по имени, телефону, адресу..."
                           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">📅 От даты</label>
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($_GET['date_from'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">📅 До даты</label>
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($_GET['date_to'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">🚚 Тип</label>
                    <select name="order_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Все типы</option>
                        <option value="astana" <?php echo ($_GET['order_type'] ?? '') === 'astana' ? 'selected' : ''; ?>>🏙️ Астана</option>
                        <option value="regional" <?php echo ($_GET['order_type'] ?? '') === 'regional' ? 'selected' : ''; ?>>🌍 Межгородские</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">📊 Статус</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Все статусы</option>
                        <option value="new" <?php echo ($_GET['status'] ?? '') === 'new' ? 'selected' : ''; ?>>🆕 Новые</option>
                        <option value="processing" <?php echo ($_GET['status'] ?? '') === 'processing' ? 'selected' : ''; ?>>⏳ В обработке</option>
                        <option value="completed" <?php echo ($_GET['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>✅ Завершенные</option>
                    </select>
                </div>
                
                <div class="md:col-span-5 flex space-x-3">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                        🔍 Применить фильтры
                    </button>
                    <a href="/admin/panel.php" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors flex items-center">
                        🔄 Сбросить
                    </a>
                    <button type="button" onclick="refreshOrders()" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors flex items-center">
                        🔄 Обновить
                    </button>
                </div>
            </form>
        </div>

        <!-- Bulk Actions Bar (Hidden by default) -->
        <div id="bulkActionsBar" class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg mb-6 hidden">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <span class="text-sm font-medium">Выбрано заказов: <span id="selectedCount">0</span></span>
                    <select id="bulkAction" class="px-3 py-1 border border-gray-300 rounded text-sm">
                        <option value="">Выберите действие</option>
                        <option value="processing">Перевести в обработку</option>
                        <option value="completed">Отметить завершенными</option>
                        <option value="delete">Удалить</option>
                    </select>
                    <button onclick="executeBulkAction()" class="bg-orange-600 text-white px-4 py-1 rounded text-sm hover:bg-orange-700">
                        Выполнить
                    </button>
                </div>
                <button onclick="clearSelection()" class="text-gray-500 hover:text-gray-700">
                    ✕ Отменить
                </button>
            </div>
        </div>
        
        <!-- Enhanced Orders Table -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-bold">Заказы (<?php echo count($orders); ?>)</h2>
                    <div class="flex space-x-2">
                        <label class="flex items-center space-x-2 text-sm">
                            <input type="checkbox" id="selectAllOrders" onchange="toggleAllOrders()" class="rounded border-gray-300">
                            <span>Выбрать все</span>
                        </label>
                        <select onchange="changeTableView(this.value)" class="text-sm border border-gray-300 rounded px-2 py-1">
                            <option value="detailed">Подробный вид</option>
                            <option value="compact">Компактный вид</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full" id="ordersTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                <input type="checkbox" id="headerCheckbox" onchange="toggleAllOrders()" class="rounded border-gray-300">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100" onclick="sortTable('id')">
                                ID 📊
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100" onclick="sortTable('type')">
                                Тип 🚚
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Маршрут 📍</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Груз 📦</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100" onclick="sortTable('contact')">
                                Контакт 👤
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100" onclick="sortTable('status')">
                                Статус 📊
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:bg-gray-100" onclick="sortTable('date')">
                                Дата 📅
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Действия ⚙️</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($orders as $order): ?>
                            <tr class="hover:bg-gray-50 transition-colors order-row" data-order-id="<?php echo $order['id']; ?>">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" class="order-checkbox rounded border-gray-300" value="<?php echo $order['id']; ?>" onchange="updateSelectedCount()">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <div class="flex items-center">
                                        <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-semibold mr-2">
                                            #<?php echo $order['id']; ?>
                                        </span>
                                        <button onclick="viewOrderDetails(<?php echo $order['id']; ?>)" class="text-blue-600 hover:text-blue-800 text-xs">
                                            👁️ Детали
                                        </button>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        <?php echo $order['order_type'] === 'astana' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'; ?>">
                                        <?php echo $order['order_type'] === 'astana' ? '🏙️ Астана' : '🌍 Межгород'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 max-w-xs">
                                    <?php if ($order['order_type'] === 'regional'): ?>
                                        <div class="font-medium text-gray-900">
                                            <?php echo htmlspecialchars($order['pickup_city'] ?? 'Не указан'); ?> → <?php echo htmlspecialchars($order['destination_city'] ?? 'Не указан'); ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="font-medium text-gray-900">Астана</div>
                                    <?php endif; ?>
                                    <div class="text-xs text-gray-400 truncate">
                                        📍 <?php echo htmlspecialchars(substr($order['pickup_address'], 0, 40)); ?>...
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <div class="font-medium text-gray-900">
                                        📦 <?php echo htmlspecialchars($order['cargo_type']); ?>
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        ⚖️ <?php echo htmlspecialchars($order['cargo_weight'] ?? 'Не указан'); ?> кг
                                    </div>
                                    <?php if ($order['cargo_dimensions']): ?>
                                        <div class="text-xs text-gray-400">
                                            📏 <?php echo htmlspecialchars($order['cargo_dimensions']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <div class="font-medium text-gray-900">
                                        👤 <?php echo htmlspecialchars($order['pickup_contact_person'] ?? 'Не указан'); ?>
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        📞 <a href="tel:<?php echo htmlspecialchars($order['pickup_contact_phone'] ?? ''); ?>" class="hover:text-blue-600">
                                            <?php echo htmlspecialchars($order['pickup_contact_phone'] ?? 'Не указан'); ?>
                                        </a>
                                    </div>
                                    <?php if ($order['pickup_ready_time']): ?>
                                        <div class="text-xs text-gray-400">
                                            ⏰ <?php echo htmlspecialchars($order['pickup_ready_time']); ?>
                                        </div>
                                    <?php endif; ?>
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
                                            'new' => '🆕 Новый',
                                            'processing' => '⏳ В обработке',
                                            'completed' => '✅ Завершен',
                                            default => $order['status']
                                        };
                                        ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div class="font-medium">
                                        📅 <?php echo date('d.m.Y', strtotime($order['created_at'])); ?>
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        🕐 <?php echo date('H:i', strtotime($order['created_at'])); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="flex items-center space-x-2">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <select name="status" onchange="updateOrderStatus(this, <?php echo $order['id']; ?>)" 
                                                    class="text-xs border border-gray-300 rounded px-2 py-1 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                <option value="new" <?php echo $order['status'] === 'new' ? 'selected' : ''; ?>>🆕 Новый</option>
                                                <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>⏳ В обработке</option>
                                                <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>✅ Завершен</option>
                                            </select>
                                        </form>
                                        <button onclick="deleteOrder(<?php echo $order['id']; ?>)" class="text-red-600 hover:text-red-800 text-xs">
                                            🗑️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                    Заказы не найдены
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal for order details -->
    <div id="orderDetailsModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between pb-3">
                    <h3 class="text-lg font-medium text-gray-900">Детали заказа</h3>
                    <button onclick="closeOrderDetails()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="orderDetailsContent" class="mt-2 px-7 py-3">
                    <!-- Order details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        function viewOrderDetails(orderId) {
            // Show modal
            document.getElementById('orderDetailsModal').classList.remove('hidden');
            
            // Load order details via AJAX
            fetch(`/admin/api.php?action=get_order&id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayOrderDetails(data.order);
                    } else {
                        document.getElementById('orderDetailsContent').innerHTML = 
                            '<p class="text-red-600">Ошибка загрузки данных: ' + data.error + '</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('orderDetailsContent').innerHTML = 
                        '<p class="text-red-600">Ошибка загрузки данных</p>';
                });
        }

        function displayOrderDetails(order) {
            const content = document.getElementById('orderDetailsContent');
            const orderTypeText = order.order_type === 'astana' ? 'Доставка по Астане' : 'Межгородская доставка';
            
            content.innerHTML = `
                <form id="editOrderForm" class="space-y-6" onsubmit="saveOrderChanges(event)">
                    <input type="hidden" name="order_id" value="${order.id}">
                    
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-xl font-bold text-gray-800">Заказ #${order.id} - ${orderTypeText}</h4>
                        <div class="flex gap-2">
                            <button type="button" onclick="toggleEditMode()" id="editButton" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors">
                                ✏️ Редактировать
                            </button>
                            <button type="submit" id="saveButton" class="hidden bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition-colors">
                                💾 Сохранить
                            </button>
                            <button type="button" onclick="cancelEdit()" id="cancelButton" class="hidden bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 transition-colors">
                                ❌ Отмена
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-blue-900 mb-3">Основная информация</h4>
                            <div class="space-y-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Статус:</label>
                                    <select name="status" class="edit-field hidden w-full mt-1 border border-gray-300 rounded px-3 py-1 focus:ring-2 focus:ring-blue-500">
                                        <option value="new" ${order.status === 'new' ? 'selected' : ''}>🆕 Новый</option>
                                        <option value="processing" ${order.status === 'processing' ? 'selected' : ''}>⏳ В обработке</option>
                                        <option value="completed" ${order.status === 'completed' ? 'selected' : ''}>✅ Завершен</option>
                                        <option value="cancelled" ${order.status === 'cancelled' ? 'selected' : ''}>❌ Отменен</option>
                                    </select>
                                    <span class="view-field">${{
                                        'new': '🆕 Новый',
                                        'processing': '⏳ В обработке',
                                        'completed': '✅ Завершен',
                                        'cancelled': '❌ Отменен'
                                    }[order.status] || order.status}</span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Тип заказа:</label>
                                    <span class="view-field">${orderTypeText}</span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Дата создания:</label>
                                    <span class="view-field">${new Date(order.created_at).toLocaleString('ru-RU')}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-green-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-green-900 mb-3">Груз</h4>
                            <div class="space-y-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Тип груза:</label>
                                    <select name="cargo_type" class="edit-field hidden w-full mt-1 border border-gray-300 rounded px-3 py-1 focus:ring-2 focus:ring-blue-500">
                                        <option value="">Выберите тип груза</option>
                                        <option value="лифтовые порталы" ${order.cargo_type === 'лифтовые порталы' ? 'selected' : ''}>Лифтовые порталы</option>
                                        <option value="т-образные профили" ${order.cargo_type === 'т-образные профили' ? 'selected' : ''}>Т-образные профили</option>
                                        <option value="металлические плинтуса" ${order.cargo_type === 'металлические плинтуса' ? 'selected' : ''}>Металлические плинтуса</option>
                                        <option value="корзины для кондиционеров" ${order.cargo_type === 'корзины для кондиционеров' ? 'selected' : ''}>Корзины для кондиционеров</option>
                                        <option value="декоративные решетки" ${order.cargo_type === 'декоративные решетки' ? 'selected' : ''}>Декоративные решетки</option>
                                        <option value="перфорированные фасадные кассеты" ${order.cargo_type === 'перфорированные фасадные кассеты' ? 'selected' : ''}>Перфорированные фасадные кассеты</option>
                                        <option value="стеклянные душевые кабины" ${order.cargo_type === 'стеклянные душевые кабины' ? 'selected' : ''}>Стеклянные душевые кабины</option>
                                        <option value="зеркальные панно" ${order.cargo_type === 'зеркальные панно' ? 'selected' : ''}>Зеркальные панно</option>
                                        <option value="рамы и багеты" ${order.cargo_type === 'рамы и багеты' ? 'selected' : ''}>Рамы и багеты</option>
                                        <option value="козырьки" ${order.cargo_type === 'козырьки' ? 'selected' : ''}>Козырьки</option>
                                        <option value="документы" ${order.cargo_type === 'документы' ? 'selected' : ''}>Документы</option>
                                        <option value="образцы" ${order.cargo_type === 'образцы' ? 'selected' : ''}>Образцы</option>
                                        <option value="другое" ${order.cargo_type === 'другое' ? 'selected' : ''}>Другое</option>
                                    </select>
                                    <span class="view-field">${order.cargo_type || 'Не указан'}</span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Вес (кг):</label>
                                    <input type="number" step="0.1" name="weight" value="${order.weight || ''}" class="edit-field hidden w-full mt-1 border border-gray-300 rounded px-3 py-1 focus:ring-2 focus:ring-blue-500">
                                    <span class="view-field">${order.weight || 'Не указан'} кг</span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Габариты:</label>
                                    <input type="text" name="dimensions" value="${order.dimensions || ''}" class="edit-field hidden w-full mt-1 border border-gray-300 rounded px-3 py-1 focus:ring-2 focus:ring-blue-500">
                                    <span class="view-field">${order.dimensions || 'Не указаны'}</span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Готов к отправке:</label>
                                    <input type="time" name="ready_time" value="${order.ready_time || ''}" class="edit-field hidden w-full mt-1 border border-gray-300 rounded px-3 py-1 focus:ring-2 focus:ring-blue-500">
                                    <span class="view-field">${order.ready_time || 'Не указано'}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-900 mb-3">Адреса</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${order.order_type === 'regional' ? `
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Город отправления:</label>
                                    <input type="text" name="pickup_city" value="${order.pickup_city || ''}" class="edit-field hidden w-full mt-1 border border-gray-300 rounded px-3 py-1 focus:ring-2 focus:ring-blue-500">
                                    <span class="view-field">${order.pickup_city || 'Не указан'}</span>
                                </div>
                            ` : ''}
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Адрес забора:</label>
                                <textarea name="pickup_address" class="edit-field hidden w-full mt-1 border border-gray-300 rounded px-3 py-1 focus:ring-2 focus:ring-blue-500 rows-3">${order.pickup_address || ''}</textarea>
                                <span class="view-field">${order.pickup_address || 'Не указан'}</span>
                            </div>
                            ${order.order_type === 'regional' ? `
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Город назначения:</label>
                                    <input type="text" name="destination_city" value="${order.destination_city || ''}" class="edit-field hidden w-full mt-1 border border-gray-300 rounded px-3 py-1 focus:ring-2 focus:ring-blue-500">
                                    <span class="view-field">${order.destination_city || 'Не указан'}</span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Адрес доставки:</label>
                                    <textarea name="delivery_address" class="edit-field hidden w-full mt-1 border border-gray-300 rounded px-3 py-1 focus:ring-2 focus:ring-blue-500 rows-3">${order.delivery_address || ''}</textarea>
                                    <span class="view-field">${order.delivery_address || 'Не указан'}</span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Способ доставки:</label>
                                    <select name="delivery_method" class="edit-field hidden w-full mt-1 border border-gray-300 rounded px-3 py-1 focus:ring-2 focus:ring-blue-500">
                                        <option value="">Выберите способ</option>
                                        <option value="Курьер" ${order.delivery_method === 'Курьер' ? 'selected' : ''}>Курьер</option>
                                        <option value="Самовывоз" ${order.delivery_method === 'Самовывоз' ? 'selected' : ''}>Самовывоз</option>
                                        <option value="Терминал" ${order.delivery_method === 'Терминал' ? 'selected' : ''}>Терминал</option>
                                    </select>
                                    <span class="view-field">${order.delivery_method || 'Не указан'}</span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Желаемая дата прибытия:</label>
                                    <input type="date" name="desired_arrival_date" value="${order.desired_arrival_date || ''}" class="edit-field hidden w-full mt-1 border border-gray-300 rounded px-3 py-1 focus:ring-2 focus:ring-blue-500">
                                    <span class="view-field">${order.desired_arrival_date || 'Не указана'}</span>
                                </div>
                            ` : ''}
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-purple-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-purple-900 mb-3">Контакты отправителя</h4>
                            <div class="space-y-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Имя:</label>
                                    <input type="text" name="contact_name" value="${order.contact_name || ''}" class="edit-field hidden w-full mt-1 border border-gray-300 rounded px-3 py-1 focus:ring-2 focus:ring-blue-500">
                                    <span class="view-field">${order.contact_name || 'Не указано'}</span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Телефон:</label>
                                    <input type="tel" name="contact_phone" value="${order.contact_phone || ''}" class="edit-field hidden w-full mt-1 border border-gray-300 rounded px-3 py-1 focus:ring-2 focus:ring-blue-500">
                                    <span class="view-field">${order.contact_phone || 'Не указан'}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-orange-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-orange-900 mb-3">Контакты получателя</h4>
                            <div class="space-y-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Имя:</label>
                                    <input type="text" name="recipient_contact" value="${order.recipient_contact || ''}" class="edit-field hidden w-full mt-1 border border-gray-300 rounded px-3 py-1 focus:ring-2 focus:ring-blue-500">
                                    <span class="view-field">${order.recipient_contact || 'Не указано'}</span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Телефон:</label>
                                    <input type="tel" name="recipient_phone" value="${order.recipient_phone || ''}" class="edit-field hidden w-full mt-1 border border-gray-300 rounded px-3 py-1 focus:ring-2 focus:ring-blue-500">
                                    <span class="view-field">${order.recipient_phone || 'Не указан'}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-yellow-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-yellow-900 mb-3">Комментарий</h4>
                        <textarea name="comment" class="edit-field hidden w-full mt-1 border border-gray-300 rounded px-3 py-1 focus:ring-2 focus:ring-blue-500" rows="3">${order.comment || ''}</textarea>
                        <span class="view-field">${order.comment || 'Не указан'}</span>
                    </div>
                </form>
            `;
        }

        function closeOrderDetails() {
            document.getElementById('orderDetailsModal').classList.add('hidden');
        }

        function updateOrderStatus(selectElement, orderId) {
            const form = selectElement.closest('form');
            form.submit();
        }

        function deleteOrder(orderId) {
            if (confirm('Вы уверены, что хотите удалить этот заказ?')) {
                // Implementation for deleting order
                alert('Функция удаления будет реализована позже');
            }
        }

        function toggleEditMode() {
            const editFields = document.querySelectorAll('.edit-field');
            const viewFields = document.querySelectorAll('.view-field');
            const editButton = document.getElementById('editButton');
            const saveButton = document.getElementById('saveButton');
            const cancelButton = document.getElementById('cancelButton');
            
            editFields.forEach(field => field.classList.toggle('hidden'));
            viewFields.forEach(field => field.classList.toggle('hidden'));
            
            editButton.classList.toggle('hidden');
            saveButton.classList.toggle('hidden');
            cancelButton.classList.toggle('hidden');
        }

        function cancelEdit() {
            // Reset form and toggle back to view mode
            const currentOrderId = document.querySelector('input[name="order_id"]').value;
            
            // Reload order details
            fetch(`/admin/api.php?action=get_order&id=${currentOrderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayOrderDetails(data.order);
                    }
                });
        }

        function saveOrderChanges(event) {
            event.preventDefault();
            
            const form = document.getElementById('editOrderForm');
            const formData = new FormData(form);
            const orderData = {};
            
            for (let [key, value] of formData.entries()) {
                if (key !== 'order_id') {
                    orderData[key] = value;
                }
            }
            
            const orderId = formData.get('order_id');
            
            // Show loading state
            const saveButton = document.getElementById('saveButton');
            const originalText = saveButton.innerHTML;
            saveButton.innerHTML = '⏳ Сохранение...';
            saveButton.disabled = true;
            
            // Send update request
            fetch(`/admin/api.php?action=update_order`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: parseInt(orderId),
                    data: orderData
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    alert('Заказ успешно обновлен!');
                    
                    // Reload order details in view mode
                    displayOrderDetails(data.order);
                    
                    // Reload the main orders table
                    window.location.reload();
                } else {
                    alert('Ошибка при сохранении: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ошибка при сохранении данных');
            })
            .finally(() => {
                saveButton.innerHTML = originalText;
                saveButton.disabled = false;
            });
        }

        // Close modal when clicking outside
        document.getElementById('orderDetailsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeOrderDetails();
            }
        });
    </script>
</body>
</html>