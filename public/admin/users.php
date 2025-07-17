<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\User;
use App\Auth;

Auth::requireAuth();

$userModel = new User();
$success = '';
$error = '';

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_user') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        
        if ($username && $password) {
            try {
                $existingUser = $userModel->findByUsername($username);
                if ($existingUser) {
                    $error = 'Пользователь с таким именем уже существует';
                } else {
                    $newUser = $userModel->create($username, $password);
                    $success = 'Пользователь успешно создан';
                }
            } catch (Exception $e) {
                $error = 'Ошибка создания пользователя: ' . $e->getMessage();
            }
        } else {
            $error = 'Заполните все поля';
        }
    }
}

// Get all users (we'll implement this method)
$users = [];
try {
    // For now, we'll show just the current admin user
    $currentUser = Auth::getCurrentUser();
    if ($currentUser) {
        $users = [$userModel->findById($currentUser['id'])];
    }
} catch (Exception $e) {
    $error = 'Ошибка загрузки пользователей: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями - Хром-KZ Логистика</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
                        <p class="text-sm text-gray-600">Управление пользователями</p>
                    </div>
                </div>
                <div class="flex space-x-4">
                    <a href="/admin/dashboard.php" class="text-gray-600 hover:text-blue-600 transition-colors">Дашборд</a>
                    <a href="/admin/panel.php" class="text-gray-600 hover:text-blue-600 transition-colors">Заказы</a>
                    <a href="/" class="text-gray-600 hover:text-blue-600 transition-colors">Главная</a>
                    <a href="/admin/logout.php" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition-colors">Выйти</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Управление пользователями</h1>
            <p class="text-gray-600 mt-2">Создание и управление пользователями системы</p>
        </div>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Create User Form -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-bold mb-6">Создать нового пользователя</h2>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="create_user">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Имя пользователя</label>
                            <input type="text" name="username" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Пароль</label>
                            <input type="password" name="password" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">Минимум 6 символов</p>
                        </div>
                        
                        <button type="submit" 
                                class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                            Создать пользователя
                        </button>
                    </form>
                </div>

                <!-- System Info -->
                <div class="bg-white rounded-xl shadow-md p-6 mt-6">
                    <h3 class="text-lg font-semibold mb-4">Информация о системе</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Всего пользователей:</span>
                            <span class="font-medium"><?php echo count($users); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Активных сессий:</span>
                            <span class="font-medium">1</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Версия системы:</span>
                            <span class="font-medium">1.0.0</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users List -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b">
                        <h2 class="text-xl font-bold">Список пользователей</h2>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Имя пользователя</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Дата создания</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Действия</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($users as $user): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo $user['id']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                                    <span class="text-blue-600 font-medium text-sm">
                                                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                                    </span>
                                                </div>
                                                <?php echo htmlspecialchars($user['username']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Активен
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <button class="text-blue-600 hover:text-blue-900 mr-3" onclick="editUser(<?php echo $user['id']; ?>)">
                                                Редактировать
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                            Пользователи не найдены
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- User Management Tools -->
                <div class="bg-white rounded-xl shadow-md p-6 mt-6">
                    <h3 class="text-lg font-semibold mb-4">Инструменты управления</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <button class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition-colors">
                            Экспорт пользователей
                        </button>
                        <button class="bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition-colors">
                            Резервное копирование
                        </button>
                        <button class="bg-yellow-600 text-white py-2 px-4 rounded-md hover:bg-yellow-700 transition-colors">
                            Очистка сессий
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function editUser(userId) {
            alert('Редактирование пользователя #' + userId + ' будет реализовано в следующей версии');
        }
    </script>
</body>
</html>