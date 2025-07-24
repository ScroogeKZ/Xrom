<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\CRM\CRMAuth;
use App\Models\Driver;
use App\Models\Carrier;

// Проверка авторизации с правами на просмотр водителей
CRMAuth::requireCRMAuth('drivers', 'read');

$driver = new Driver();
$carrier = new Carrier();

// Получение всех водителей
$drivers = $driver->getAll();
$carriers = $carrier->getAll();
$currentUser = CRMAuth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Водители - CRM Хром-KZ</title>
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
                        <h1 class="text-2xl font-bold text-gray-900">Водители</h1>
                        <p class="text-gray-600">Управление водителями и их статусами</p>
                    </div>
                    
                    <?php if (CRMAuth::can('drivers', 'create')): ?>
                    <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-plus mr-2"></i>
                        Добавить водителя
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Статистика -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center">
                            <div class="p-3 bg-green-100 rounded-full">
                                <i class="fas fa-user-check text-green-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    <?= count(array_filter($drivers, fn($d) => $d['status'] === 'available')) ?>
                                </h3>
                                <p class="text-gray-600">Доступно</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center">
                            <div class="p-3 bg-yellow-100 rounded-full">
                                <i class="fas fa-user-clock text-yellow-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    <?= count(array_filter($drivers, fn($d) => $d['status'] === 'busy')) ?>
                                </h3>
                                <p class="text-gray-600">Занято</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center">
                            <div class="p-3 bg-blue-100 rounded-full">
                                <i class="fas fa-users text-blue-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900"><?= count($drivers) ?></h3>
                                <p class="text-gray-600">Всего водителей</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center">
                            <div class="p-3 bg-purple-100 rounded-full">
                                <i class="fas fa-star text-purple-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    <?= number_format(array_sum(array_column($drivers, 'rating')) / max(count($drivers), 1), 1) ?>
                                </h3>
                                <p class="text-gray-600">Средний рейтинг</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Таблица водителей -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h2 class="text-lg font-semibold text-gray-900">Список водителей</h2>
                            <div class="flex space-x-2">
                                <input type="text" placeholder="Поиск..." class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <select class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <option value="">Все статусы</option>
                                    <option value="available">Доступен</option>
                                    <option value="busy">Занят</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Водитель</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Перевозчик</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Телефон</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Лицензия</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Рейтинг</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Действия</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($drivers as $driverItem): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
                                                <span class="text-white text-sm font-medium">
                                                    <?= strtoupper(substr($driverItem['first_name'], 0, 1) . substr($driverItem['last_name'], 0, 1)) ?>
                                                </span>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?= htmlspecialchars($driverItem['first_name'] . ' ' . $driverItem['last_name']) ?>
                                                </div>
                                                <div class="text-sm text-gray-500">ID: <?= $driverItem['id'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900"><?= htmlspecialchars($driverItem['carrier_name'] ?? 'Не назначен') ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900"><?= htmlspecialchars($driverItem['phone']) ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900"><?= htmlspecialchars($driverItem['license_number']) ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex text-yellow-400">
                                                <?php for($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?= $i <= floor($driverItem['rating']) ? 'text-yellow-400' : 'text-gray-300' ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <span class="ml-2 text-sm text-gray-600"><?= number_format($driverItem['rating'], 1) ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php
                                        $statusColors = [
                                            'available' => 'bg-green-100 text-green-800',
                                            'busy' => 'bg-yellow-100 text-yellow-800',
                                            'offline' => 'bg-gray-100 text-gray-800'
                                        ];
                                        $statusText = [
                                            'available' => 'Доступен',
                                            'busy' => 'Занят',
                                            'offline' => 'Не в сети'
                                        ];
                                        ?>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $statusColors[$driverItem['status']] ?? 'bg-gray-100 text-gray-800' ?>">
                                            <?= $statusText[$driverItem['status']] ?? 'Неизвестно' ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex space-x-2">
                                            <?php if (CRMAuth::can('drivers', 'update')): ?>
                                            <button class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <button class="text-gray-600 hover:text-gray-900">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <?php if (CRMAuth::can('drivers', 'delete')): ?>
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
                </div>
            </div>
        </div>
    </div>
</body>
</html>