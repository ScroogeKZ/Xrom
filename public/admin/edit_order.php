<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;
use App\Models\ShipmentOrder;

Auth::requireAuth();

$orderModel = new ShipmentOrder();
$message = '';
$error = '';

// Получаем ID заказа
$orderId = (int)($_GET['id'] ?? 0);
if (!$orderId) {
    header('Location: /admin/orders.php');
    exit;
}

// Получаем данные заказа
$order = $orderModel->getById($orderId);
if (!$order) {
    $_SESSION['error'] = 'Заказ не найден';
    header('Location: /admin/orders.php');
    exit;
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $updateData = [
            'order_type' => $_POST['order_type'],
            'pickup_address' => $_POST['pickup_address'],
            'ready_time' => $_POST['ready_time'],
            'cargo_type' => $_POST['cargo_type'],
            'weight' => $_POST['weight'],
            'dimensions' => $_POST['dimensions'],
            'contact_name' => $_POST['contact_name'],
            'contact_phone' => $_POST['contact_phone'],
            'notes' => $_POST['notes'] ?? '',
            'status' => $_POST['status']
        ];

        // Поля для региональных заказов
        if ($_POST['order_type'] === 'regional') {
            $updateData['pickup_city'] = $_POST['pickup_city'] ?? '';
            $updateData['destination_city'] = $_POST['destination_city'] ?? '';
            $updateData['delivery_address'] = $_POST['delivery_address'] ?? '';
            $updateData['delivery_method'] = $_POST['delivery_method'] ?? '';
            $updateData['desired_arrival_date'] = $_POST['desired_arrival_date'] ?? '';
        } else {
            // Очищаем региональные поля для астанинских заказов
            $updateData['pickup_city'] = null;
            $updateData['destination_city'] = null;
            $updateData['delivery_address'] = null;
            $updateData['delivery_method'] = null;
            $updateData['desired_arrival_date'] = null;
        }

        $orderModel->update($orderId, $updateData);
        $message = 'Заказ успешно обновлен';
        
        // Обновляем данные заказа
        $order = $orderModel->getById($orderId);
        
    } catch (Exception $e) {
        $error = 'Ошибка обновления заказа: ' . $e->getMessage();
    }
}

$currentUser = Auth::getCurrentUser();

// Список городов Казахстана
$kazakhstanCities = [
    'Алматы', 'Нур-Султан', 'Шымкент', 'Актобе', 'Тараз', 'Павлодар', 'Усть-Каменогорск', 
    'Семей', 'Атырау', 'Костанай', 'Петропавловск', 'Орал', 'Темиртау', 'Актау', 'Кокшетау',
    'Талдыкорган', 'Экибастуз', 'Рудный', 'Жанаозен', 'Балхаш', 'Житикара', 'Каскелен',
    'Кентау', 'Лисаковск', 'Сарань', 'Степногорск', 'Шахтинск', 'Капшагай', 'Аксу',
    'Зыряновск', 'Кандыагаш', 'Жезказган', 'Форт-Шевченко', 'Приозерск', 'Макинск'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование заказа #<?php echo $order['id']; ?> - Хром-KZ Логистика</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                        <p class="text-sm text-gray-600 font-medium">Редактирование заказа</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/admin/" class="text-gray-600 hover:text-primary transition-colors">Дашборд</a>
                    <a href="/admin/orders.php" class="text-gray-600 hover:text-primary transition-colors">Заказы</a>
                    <span class="text-gray-600">Добро пожаловать, <strong><?php echo htmlspecialchars($currentUser['username']); ?></strong></span>
                    <a href="/admin/logout.php" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">Выйти</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <!-- Заголовок -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">Редактирование заказа #<?php echo $order['id']; ?></h1>
            <p class="text-gray-600">Изменение данных заказа</p>
        </div>

        <!-- Сообщения -->
        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Форма редактирования -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <form method="POST" class="space-y-6">
                <!-- Основная информация -->
                <div>
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Основная информация</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Тип заказа</label>
                            <select name="order_type" id="order_type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" onchange="toggleRegionalFields()">
                                <option value="astana" <?php echo $order['order_type'] === 'astana' ? 'selected' : ''; ?>>Астана</option>
                                <option value="regional" <?php echo $order['order_type'] === 'regional' ? 'selected' : ''; ?>>Регионы</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Статус заказа</label>
                            <select name="status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                <option value="new" <?php echo $order['status'] === 'new' ? 'selected' : ''; ?>>Новый</option>
                                <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>В работе</option>
                                <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Завершен</option>
                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Отменен</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Контактная информация -->
                <div>
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Контактная информация</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Имя контактного лица *</label>
                            <input type="text" name="contact_name" value="<?php echo htmlspecialchars($order['contact_name'] ?? ''); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Телефон *</label>
                            <input type="tel" name="contact_phone" value="<?php echo htmlspecialchars($order['contact_phone'] ?? ''); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                    </div>
                </div>

                <!-- Информация о грузе -->
                <div>
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Информация о грузе</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Тип груза *</label>
                            <input type="text" name="cargo_type" value="<?php echo htmlspecialchars($order['cargo_type'] ?? ''); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Вес *</label>
                            <input type="text" name="weight" value="<?php echo htmlspecialchars($order['weight'] ?? ''); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Габариты *</label>
                            <input type="text" name="dimensions" value="<?php echo htmlspecialchars($order['dimensions'] ?? ''); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Время готовности к отправке *</label>
                            <input type="time" name="ready_time" value="<?php echo htmlspecialchars($order['ready_time'] ?? ''); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                    </div>
                </div>

                <!-- Адреса -->
                <div>
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Адреса</h2>
                    
                    <!-- Поля для региональных заказов -->
                    <div id="regional_fields" class="space-y-4 <?php echo $order['order_type'] !== 'regional' ? 'hidden' : ''; ?>">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Город отправления</label>
                                <select name="pickup_city" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="">Выберите город</option>
                                    <?php foreach ($kazakhstanCities as $city): ?>
                                        <option value="<?php echo htmlspecialchars($city); ?>" <?php echo ($order['pickup_city'] ?? '') === $city ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($city); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Город назначения</label>
                                <select name="destination_city" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="">Выберите город</option>
                                    <?php foreach ($kazakhstanCities as $city): ?>
                                        <option value="<?php echo htmlspecialchars($city); ?>" <?php echo ($order['destination_city'] ?? '') === $city ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($city); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Способ доставки</label>
                                <select name="delivery_method" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="">Выберите способ</option>
                                    <option value="door_to_door" <?php echo ($order['delivery_method'] ?? '') === 'door_to_door' ? 'selected' : ''; ?>>От двери до двери</option>
                                    <option value="terminal_to_terminal" <?php echo ($order['delivery_method'] ?? '') === 'terminal_to_terminal' ? 'selected' : ''; ?>>Терминал-терминал</option>
                                    <option value="door_to_terminal" <?php echo ($order['delivery_method'] ?? '') === 'door_to_terminal' ? 'selected' : ''; ?>>От двери до терминала</option>
                                    <option value="terminal_to_door" <?php echo ($order['delivery_method'] ?? '') === 'terminal_to_door' ? 'selected' : ''; ?>>От терминала до двери</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Желаемая дата прибытия</label>
                                <input type="date" name="desired_arrival_date" value="<?php echo htmlspecialchars($order['desired_arrival_date'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Обязательные поля адресов -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Адрес забора груза *</label>
                            <textarea name="pickup_address" required rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"><?php echo htmlspecialchars($order['pickup_address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div id="delivery_address_field" class="<?php echo $order['order_type'] !== 'regional' ? 'hidden' : ''; ?>">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Адрес доставки</label>
                            <textarea name="delivery_address" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"><?php echo htmlspecialchars($order['delivery_address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Комментарии -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Дополнительные комментарии</label>
                    <textarea name="notes" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Дополнительная информация о заказе..."><?php echo htmlspecialchars($order['notes'] ?? ''); ?></textarea>
                </div>

                <!-- Кнопки действий -->
                <div class="flex justify-between pt-6">
                    <a href="/admin/orders.php" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        Отменить
                    </a>
                    <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-primary-dark transition-colors">
                        Сохранить изменения
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleRegionalFields() {
            const orderType = document.getElementById('order_type').value;
            const regionalFields = document.getElementById('regional_fields');
            const deliveryAddressField = document.getElementById('delivery_address_field');
            
            if (orderType === 'regional') {
                regionalFields.classList.remove('hidden');
                deliveryAddressField.classList.remove('hidden');
            } else {
                regionalFields.classList.add('hidden');
                deliveryAddressField.classList.add('hidden');
            }
        }

        // Инициализация при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            toggleRegionalFields();
        });
    </script>
</body>
</html>