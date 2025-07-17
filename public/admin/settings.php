<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;
use App\Models\User;
use App\TelegramService;

Auth::requireAuth();

$userModel = new User();
$telegramService = new TelegramService();
$message = '';
$error = '';

// Обработка форм
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'change_password':
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            if ($newPassword !== $confirmPassword) {
                $error = 'Новые пароли не совпадают';
                break;
            }
            
            $currentUser = Auth::getCurrentUser();
            $user = $userModel->getById($currentUser['id']);
            
            if (!$userModel->verifyPassword($currentPassword, $user['password'])) {
                $error = 'Неверный текущий пароль';
                break;
            }
            
            try {
                $userModel->updatePassword($currentUser['id'], $newPassword);
                $message = 'Пароль успешно изменен';
            } catch (Exception $e) {
                $error = 'Ошибка изменения пароля: ' . $e->getMessage();
            }
            break;
            
        case 'create_user':
            $username = $_POST['username'];
            $password = $_POST['password'];
            
            if (empty($username) || empty($password)) {
                $error = 'Заполните все поля';
                break;
            }
            
            try {
                $userModel->create(['username' => $username, 'password' => $password]);
                $message = 'Пользователь успешно создан';
            } catch (Exception $e) {
                $error = 'Ошибка создания пользователя: ' . $e->getMessage();
            }
            break;
            
        case 'test_telegram':
            try {
                $testMessage = "🧪 Тест уведомлений Telegram\n\nЭто тестовое сообщение из админ-панели Хром-KZ Логистика.\nВремя: " . date('d.m.Y H:i:s');
                
                if ($telegramService->isConfigured()) {
                    // Используем базовый метод отправки
                    $result = $telegramService->sendMessage($testMessage);
                    if ($result) {
                        $message = 'Тестовое сообщение успешно отправлено в Telegram';
                    } else {
                        $error = 'Не удалось отправить тестовое сообщение. Проверьте настройки Telegram';
                    }
                } else {
                    $error = 'Telegram не настроен. Проверьте переменные окружения TELEGRAM_BOT_TOKEN и TELEGRAM_CHAT_ID';
                }
            } catch (Exception $e) {
                $error = 'Ошибка тестирования Telegram: ' . $e->getMessage();
            }
            break;
    }
}

// Получаем список пользователей
$users = $userModel->getAll();
$currentUser = Auth::getCurrentUser();

// Проверяем статус Telegram
$telegramConfigured = $telegramService->isConfigured();
$telegramBotToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? getenv('TELEGRAM_BOT_TOKEN');
$telegramChatId = $_ENV['TELEGRAM_CHAT_ID'] ?? getenv('TELEGRAM_CHAT_ID');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки - Хром-KZ Логистика</title>
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
                        <p class="text-sm text-gray-600 font-medium">Настройки системы</p>
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

    <div class="max-w-6xl mx-auto px-4 py-8">
        <!-- Заголовок -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">Настройки системы</h1>
            <p class="text-gray-600">Управление пользователями и конфигурацией</p>
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

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Смена пароля -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Смена пароля</h2>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Текущий пароль</label>
                        <input type="password" name="current_password" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Новый пароль</label>
                        <input type="password" name="new_password" required minlength="6" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Подтвердите новый пароль</label>
                        <input type="password" name="confirm_password" required minlength="6" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    
                    <button type="submit" class="w-full bg-primary text-white py-2 rounded-lg hover:bg-primary-dark transition-colors">
                        Изменить пароль
                    </button>
                </form>
            </div>

            <!-- Создание пользователя -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Создать нового пользователя</h2>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="create_user">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Имя пользователя</label>
                        <input type="text" name="username" required minlength="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Пароль</label>
                        <input type="password" name="password" required minlength="6" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    
                    <button type="submit" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 transition-colors">
                        Создать пользователя
                    </button>
                </form>
            </div>

            <!-- Telegram настройки -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Настройки Telegram</h2>
                
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div>
                            <p class="font-medium text-gray-800">Статус Telegram Bot</p>
                            <p class="text-sm text-gray-600">
                                <?php if ($telegramConfigured): ?>
                                    Настроен и готов к работе
                                <?php else: ?>
                                    Не настроен. Требуется настройка переменных окружения.
                                <?php endif; ?>
                            </p>
                        </div>
                        <span class="px-3 py-1 text-sm rounded-full <?php echo $telegramConfigured ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo $telegramConfigured ? 'Активен' : 'Неактивен'; ?>
                        </span>
                    </div>
                    
                    <div class="space-y-2">
                        <div>
                            <span class="text-sm text-gray-600">Bot Token:</span>
                            <span class="ml-2 text-sm font-mono <?php echo $telegramBotToken ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo $telegramBotToken ? '••••••••••••••••••••' : 'Не установлен'; ?>
                            </span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600">Chat ID:</span>
                            <span class="ml-2 text-sm font-mono <?php echo $telegramChatId ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo $telegramChatId ? htmlspecialchars($telegramChatId) : 'Не установлен'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if ($telegramConfigured): ?>
                        <form method="POST" class="mt-4">
                            <input type="hidden" name="action" value="test_telegram">
                            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                Отправить тестовое сообщение
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <p class="text-sm text-yellow-800">
                                <strong>Для настройки Telegram:</strong><br>
                                1. Создайте бота через @BotFather<br>
                                2. Получите токен бота<br>
                                3. Узнайте Chat ID вашей группы или канала<br>
                                4. Установите переменные окружения TELEGRAM_BOT_TOKEN и TELEGRAM_CHAT_ID
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Список пользователей -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Пользователи системы</h2>
                
                <div class="space-y-3">
                    <?php foreach ($users as $user): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($user['username']); ?></p>
                                <p class="text-sm text-gray-600">Создан: <?php echo date('d.m.Y', strtotime($user['created_at'])); ?></p>
                            </div>
                            <?php if ($user['id'] == $currentUser['id']): ?>
                                <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">Вы</span>
                            <?php else: ?>
                                <span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded-full">Админ</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Системная информация -->
            <div class="bg-white rounded-xl shadow-lg p-6 lg:col-span-2">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Системная информация</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <h3 class="font-semibold text-gray-800 mb-2">PHP Версия</h3>
                        <p class="text-lg font-mono text-blue-600"><?php echo PHP_VERSION; ?></p>
                    </div>
                    
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <h3 class="font-semibold text-gray-800 mb-2">База данных</h3>
                        <p class="text-lg text-green-600">PostgreSQL</p>
                    </div>
                    
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <h3 class="font-semibold text-gray-800 mb-2">Окружение</h3>
                        <p class="text-lg text-purple-600">Replit</p>
                    </div>
                </div>
                
                <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                    <h3 class="font-semibold text-blue-800 mb-2">Статус системы</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div class="flex justify-between">
                            <span>База данных:</span>
                            <span class="text-green-600 font-semibold">✓ Подключена</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Аутентификация:</span>
                            <span class="text-green-600 font-semibold">✓ Работает</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Формы заказов:</span>
                            <span class="text-green-600 font-semibold">✓ Активны</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Telegram Bot:</span>
                            <span class="<?php echo $telegramConfigured ? 'text-green-600' : 'text-yellow-600'; ?> font-semibold">
                                <?php echo $telegramConfigured ? '✓ Настроен' : '⚠ Требует настройки'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>