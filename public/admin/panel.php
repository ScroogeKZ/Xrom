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
                                        ⚖️ <?php echo htmlspecialchars($order['weight']); ?> кг
                                    </div>
                                    <?php if ($order['dimensions']): ?>
                                        <div class="text-xs text-gray-400">
                                            📏 <?php echo htmlspecialchars($order['dimensions']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <div class="font-medium text-gray-900">
                                        👤 <?php echo htmlspecialchars($order['contact_name']); ?>
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        📞 <a href="tel:<?php echo htmlspecialchars($order['contact_phone']); ?>" class="hover:text-blue-600">
                                            <?php echo htmlspecialchars($order['contact_phone']); ?>
                                        </a>
                                    </div>
                                    <?php if ($order['ready_time']): ?>
                                        <div class="text-xs text-gray-400">
                                            ⏰ <?php echo htmlspecialchars($order['ready_time']); ?>
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
</body>
</html>