<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\CRM\CRMAuth;
use App\CRM\RoleManager;

// Проверка авторизации (только для супер-админов)
CRMAuth::requireCRMAuth();

if (!CRMAuth::hasRole(['super_admin'])) {
    header('HTTP/1.1 403 Forbidden');
    include '403.php';
    exit;
}

$roleManager = new RoleManager();
$roles = $roleManager->getAllRoles();

// Получение статистики по ролям
$roleStats = [];
foreach ($roles as $role) {
    $stmt = $roleManager->db->prepare("SELECT COUNT(*) as count FROM user_roles WHERE role_id = ?");
    $stmt->execute([$role['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $roleStats[$role['id']] = $result ? $result['count'] : 0;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Роли и права - CRM Хром-KZ</title>
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
                <!-- Заголовок -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Роли и права доступа</h1>
                    <p class="text-gray-600">Управление ролями пользователей и их правами в системе</p>
                </div>

                <!-- Статистика ролей -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
                    <?php foreach ($roles as $role): ?>
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="text-center">
                            <div class="p-3 bg-blue-100 rounded-full w-12 h-12 mx-auto mb-3 flex items-center justify-center">
                                <i class="fas fa-shield-alt text-blue-600"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900"><?= isset($roleStats[$role['id']]) ? $roleStats[$role['id']] : 0 ?></h3>
                            <p class="text-gray-600 text-sm"><?= htmlspecialchars($role['display_name']) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Таблица ролей -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Описание ролей</h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Роль</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Описание</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Пользователей</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Права</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($roles as $role): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="p-2 bg-blue-100 rounded-full mr-3">
                                                <i class="fas fa-shield-alt text-blue-600"></i>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?= htmlspecialchars($role['display_name']) ?>
                                                </div>
                                                <div class="text-sm text-gray-500"><?= htmlspecialchars($role['name']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900"><?= htmlspecialchars($role['description']) ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                            <?= isset($roleStats[$role['id']]) ? $roleStats[$role['id']] : 0 ?> пользователей
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <button class="text-blue-600 hover:text-blue-900 text-sm" onclick="showPermissions('<?= htmlspecialchars($role['name']) ?>')">
                                            <i class="fas fa-eye mr-1"></i>
                                            Просмотр прав
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Матрица прав доступа -->
                <div class="mt-8 bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Матрица прав доступа</h2>
                        <p class="text-gray-600 text-sm">Обзор всех разрешений для каждой роли</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ресурс/Действие</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Супер Админ</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Админ</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Менеджер</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Оператор</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Наблюдатель</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php
                                $resources = ['users', 'orders', 'carriers', 'vehicles', 'drivers', 'reports', 'settings'];
                                $actions = ['create', 'read', 'update', 'delete'];
                                
                                foreach ($resources as $resource):
                                    foreach ($actions as $action):
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 text-sm font-medium text-gray-900">
                                        <?= ucfirst($resource) ?> - <?= ucfirst($action) ?>
                                    </td>
                                    <?php foreach ($roles as $role): 
                                        $permissions = json_decode($role['permissions'], true);
                                        $hasPermission = false;
                                        
                                        if (isset($permissions['all']) && $permissions['all']) {
                                            $hasPermission = true;
                                        } elseif (isset($permissions[$resource][$action]) && $permissions[$resource][$action]) {
                                            $hasPermission = true;
                                        }
                                    ?>
                                    <td class="px-6 py-3 text-center">
                                        <?php if ($hasPermission): ?>
                                            <i class="fas fa-check text-green-600"></i>
                                        <?php else: ?>
                                            <i class="fas fa-times text-red-600"></i>
                                        <?php endif; ?>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php 
                                    endforeach;
                                endforeach; 
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function showPermissions(roleName) {
        alert('Права для роли: ' + roleName);
    }
    </script>
</body>
</html>