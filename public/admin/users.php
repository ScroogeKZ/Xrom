<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\CRM\CRMAuth;
use App\CRM\RoleManager;

// Проверка авторизации с правами на управление пользователями
CRMAuth::requireCRMAuth('users', 'read');

$roleManager = new RoleManager();
$currentUser = CRMAuth::getCurrentUser();

// Получение всех пользователей (упрощенная версия без ролей)
$stmt = $roleManager->db->prepare("
    SELECT u.*, 
           ARRAY['admin'] as roles,
           ARRAY['Администратор'] as role_names
    FROM users u
    WHERE u.id IS NOT NULL
    ORDER BY u.created_at DESC
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Простой список ролей для отображения
$allRoles = [
    ['name' => 'admin', 'display_name' => 'Администратор'],
    ['name' => 'manager', 'display_name' => 'Менеджер'],
    ['name' => 'operator', 'display_name' => 'Оператор']
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Пользователи - CRM Хром-KZ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50">
    <div class="flex">
        <!-- Боковое меню -->
        <?php include 'components/crm_sidebar.php'; ?>

        <!-- Основной контент -->
        <div class="flex-1 ml-64">
            <!-- Верхняя панель -->
            <?php include 'components/crm_header.php'; ?>

            <!-- Контент страницы -->
            <div class="p-6">
                <!-- Заголовок и кнопки действий -->
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Управление пользователями</h1>
                        <p class="text-gray-600">Управление пользователями системы и их ролями</p>
                    </div>
                    
                    <?php if (CRMAuth::can('users', 'create')): ?>
                    <button @click="showAddModal = true" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-plus mr-2"></i>
                        Добавить пользователя
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Статистика пользователей -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center">
                            <div class="p-3 bg-blue-100 rounded-full">
                                <i class="fas fa-users text-blue-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900"><?= count($users) ?></h3>
                                <p class="text-gray-600">Всего пользователей</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center">
                            <div class="p-3 bg-green-100 rounded-full">
                                <i class="fas fa-user-check text-green-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    <?= count(array_filter($users, fn($u) => $u['is_active'])) ?>
                                </h3>
                                <p class="text-gray-600">Активных</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center">
                            <div class="p-3 bg-yellow-100 rounded-full">
                                <i class="fas fa-shield-alt text-yellow-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    <?php 
                                    $adminCount = 0;
                                    foreach ($users as $u) {
                                        $roles = $u['roles'] ?? [];
                                        if (is_string($roles)) {
                                            $roles = trim($roles, '{}');
                                            $roles = $roles ? explode(',', $roles) : [];
                                        }
                                        if (in_array('admin', $roles) || in_array('super_admin', $roles)) {
                                            $adminCount++;
                                        }
                                    }
                                    echo $adminCount;
                                    ?>
                                
                                </h3>
                                <p class="text-gray-600">Администраторов</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center">
                            <div class="p-3 bg-purple-100 rounded-full">
                                <i class="fas fa-clock text-purple-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    <?= count(array_filter($users, fn($u) => $u['last_login'] && strtotime($u['last_login']) > strtotime('-7 days'))) ?>
                                </h3>
                                <p class="text-gray-600">Активность 7 дней</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Таблица пользователей -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200" x-data="{showAddModal: false, showEditModal: false, editingUser: {}}">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h2 class="text-lg font-semibold text-gray-900">Список пользователей</h2>
                            <div class="flex space-x-2">
                                <input type="text" placeholder="Поиск пользователей..." class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <select class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <option value="">Все роли</option>
                                    <?php foreach ($allRoles as $role): ?>
                                    <option value="<?= $role['name'] ?>"><?= htmlspecialchars($role['display_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Пользователь</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Контакты</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Роли</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Должность</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Последний вход</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Действия</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($users as $user): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
                                                <span class="text-white text-sm font-medium">
                                                    <?= strtoupper(substr($user['first_name'] ?? $user['username'], 0, 1)) ?>
                                                </span>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?>
                                                </div>
                                                <div class="text-sm text-gray-500">@<?= htmlspecialchars($user['username']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900"><?= htmlspecialchars($user['email'] ?? '') ?></div>
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($user['phone'] ?? '') ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-1">
                                            <?php 
                                            $roleNames = $user['role_names'];
                                            if (is_string($roleNames)) {
                                                $roleNames = trim($roleNames, '{}');
                                                $roleNames = $roleNames ? explode(',', $roleNames) : [];
                                            }
                                            if (!empty($roleNames)): ?>
                                                <?php foreach ($roleNames as $roleName): ?>
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    <?= htmlspecialchars(trim($roleName)) ?>
                                                </span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                            <span class="text-gray-500 text-sm">Роли не назначены</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900"><?= htmlspecialchars($user['position'] ?? '') ?></div>
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($user['department'] ?? '') ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <?= $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Никогда' ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $user['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                            <?= $user['is_active'] ? 'Активен' : 'Неактивен' ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex space-x-2">
                                            <?php if (CRMAuth::can('users', 'update')): ?>
                                            <button @click="editingUser = <?= htmlspecialchars(json_encode($user)) ?>; showEditModal = true" class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <button class="text-gray-600 hover:text-gray-900">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <?php if (CRMAuth::can('users', 'delete') && $user['id'] !== $currentUser['id']): ?>
                                            <button class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Модальное окно добавления пользователя -->
                    <div x-show="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" x-transition>
                        <div class="bg-white rounded-lg max-w-md w-full mx-4">
                            <div class="p-6 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">Добавить пользователя</h3>
                            </div>
                            <form class="p-6">
                                <div class="space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Имя</label>
                                            <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Фамилия</label>
                                            <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Логин</label>
                                        <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                        <input type="email" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Роли</label>
                                        <div class="space-y-2">
                                            <?php foreach ($allRoles as $role): ?>
                                            <label class="flex items-center">
                                                <input type="checkbox" value="<?= $role['id'] ?>" class="mr-2">
                                                <span class="text-sm"><?= htmlspecialchars($role['display_name']) ?></span>
                                            </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-end space-x-2 mt-6">
                                    <button type="button" @click="showAddModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                                        Отмена
                                    </button>
                                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                        Создать
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>