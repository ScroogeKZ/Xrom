<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\CRM\CRMAuth;

// Проверка авторизации
CRMAuth::requireCRMAuth();

$currentUser = CRMAuth::getCurrentUser();
$db = Database::getInstance()->getConnection();

// Получаем параметры фильтрации
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-t');
$orderType = $_GET['order_type'] ?? '';
$carrierId = $_GET['carrier_id'] ?? '';

// Базовый запрос
$whereClause = "WHERE so.created_at BETWEEN :date_from AND :date_to";
$params = [
    'date_from' => $dateFrom . ' 00:00:00',
    'date_to' => $dateTo . ' 23:59:59'
];

if ($orderType) {
    $whereClause .= " AND so.order_type = :order_type";
    $params['order_type'] = $orderType;
}

if ($carrierId) {
    $whereClause .= " AND so.carrier_id = :carrier_id";
    $params['carrier_id'] = $carrierId;
}

// CRM статистика
$crmStatsQuery = "
    SELECT 
        COUNT(so.id) as total_orders,
        COUNT(CASE WHEN so.status = 'new' THEN 1 END) as new_orders,
        COUNT(CASE WHEN so.status = 'in_progress' THEN 1 END) as in_progress_orders,
        COUNT(CASE WHEN so.status = 'completed' THEN 1 END) as completed_orders,
        COUNT(CASE WHEN so.order_type = 'astana' THEN 1 END) as astana_orders,
        COUNT(CASE WHEN so.order_type = 'regional' THEN 1 END) as regional_orders,
        COUNT(CASE WHEN so.order_type = 'international' THEN 1 END) as international_orders,
        COALESCE(AVG(so.shipping_cost), 0) as avg_cost,
        COALESCE(SUM(so.shipping_cost), 0) as total_costs,
        COUNT(DISTINCT so.carrier_id) as active_carriers,
        COUNT(DISTINCT so.vehicle_id) as vehicles_used,
        COUNT(DISTINCT so.driver_id) as drivers_used
    FROM shipment_orders so $whereClause
";

$stmt = $db->prepare($crmStatsQuery);
$stmt->execute($params);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Статистика по перевозчикам
$carrierStatsQuery = "
    SELECT 
        c.name as carrier_name,
        COUNT(so.id) as orders_count,
        COALESCE(SUM(so.shipping_cost), 0) as total_revenue,
        COALESCE(AVG(so.shipping_cost), 0) as avg_cost,
        c.rating
    FROM carriers c
    LEFT JOIN shipment_orders so ON c.id = so.carrier_id 
    AND so.created_at BETWEEN :date_from AND :date_to
    GROUP BY c.id, c.name, c.rating
    ORDER BY orders_count DESC
";

$stmt = $db->prepare($carrierStatsQuery);
$stmt->execute($params);
$carrierStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Статистика по водителям
$driverStatsQuery = "
    SELECT 
        CONCAT(d.first_name, ' ', d.last_name) as driver_name,
        c.name as carrier_name,
        COUNT(so.id) as orders_count,
        d.phone
    FROM drivers d
    LEFT JOIN carriers c ON d.carrier_id = c.id
    LEFT JOIN shipment_orders so ON d.id = so.driver_id 
    AND so.created_at BETWEEN :date_from AND :date_to
    GROUP BY d.id, d.first_name, d.last_name, c.name, d.phone
    HAVING COUNT(so.id) > 0
    ORDER BY orders_count DESC
    LIMIT 10
";

$stmt = $db->prepare($driverStatsQuery);
$stmt->execute($params);
$driverStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Статистика по транспорту
$vehicleStatsQuery = "
    SELECT 
        CONCAT(v.make, ' ', v.model) as vehicle_name,
        v.license_plate,
        c.name as carrier_name,
        COUNT(so.id) as orders_count,
        v.vehicle_type
    FROM vehicles v
    LEFT JOIN carriers c ON v.carrier_id = c.id
    LEFT JOIN shipment_orders so ON v.id = so.vehicle_id 
    AND so.created_at BETWEEN :date_from AND :date_to
    GROUP BY v.id, v.make, v.model, v.license_plate, c.name, v.vehicle_type
    HAVING COUNT(so.id) > 0
    ORDER BY orders_count DESC
    LIMIT 10
";

$stmt = $db->prepare($vehicleStatsQuery);
$stmt->execute($params);
$vehicleStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем список всех перевозчиков для фильтра
$carriersQuery = "SELECT id, name FROM carriers WHERE is_active = true ORDER BY name";
$stmt = $db->prepare($carriersQuery);
$stmt->execute();
$carriers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Отчеты - Хром-KZ Логистика</title>
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
                <!-- Заголовок и фильтры -->
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">CRM Отчеты</h1>
                            <p class="text-gray-600">Аналитика работы логистической компании</p>
                        </div>
                        <div class="flex space-x-3">
                            <button onclick="window.print()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                <i class="fas fa-print mr-2"></i>
                                Печать
                            </button>
                        </div>
                    </div>

                    <!-- Фильтры -->
                    <form method="GET" class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Дата от</label>
                                <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Дата до</label>
                                <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Тип заказа</label>
                                <select name="order_type" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                    <option value="">Все типы</option>
                                    <option value="astana" <?= $orderType == 'astana' ? 'selected' : '' ?>>Астана</option>
                                    <option value="regional" <?= $orderType == 'regional' ? 'selected' : '' ?>>Региональные</option>
                                    <option value="international" <?= $orderType == 'international' ? 'selected' : '' ?>>Международные</option>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                    <i class="fas fa-filter mr-2"></i>
                                    Применить фильтр
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Основная статистика -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center">
                            <div class="p-3 bg-blue-100 rounded-full">
                                <i class="fas fa-box text-blue-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Всего заказов</p>
                                <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['total_orders']) ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center">
                            <div class="p-3 bg-green-100 rounded-full">
                                <i class="fas fa-tenge text-green-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Общие затраты</p>
                                <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['total_costs'], 0) ?> ₸</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center">
                            <div class="p-3 bg-purple-100 rounded-full">
                                <i class="fas fa-building text-purple-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Активных перевозчиков</p>
                                <p class="text-2xl font-bold text-gray-900"><?= $stats['active_carriers'] ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center">
                            <div class="p-3 bg-orange-100 rounded-full">
                                <i class="fas fa-chart-line text-orange-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Средняя стоимость</p>
                                <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['avg_cost'], 0) ?> ₸</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Статистика по статусам -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Распределение по статусам</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Новые</span>
                                <span class="font-bold text-orange-600"><?= $stats['new_orders'] ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">В работе</span>
                                <span class="font-bold text-blue-600"><?= $stats['in_progress_orders'] ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Завершенные</span>
                                <span class="font-bold text-green-600"><?= $stats['completed_orders'] ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Распределение по типам</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Астана</span>
                                <span class="font-bold text-blue-600"><?= $stats['astana_orders'] ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Региональные</span>
                                <span class="font-bold text-purple-600"><?= $stats['regional_orders'] ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Международные</span>
                                <span class="font-bold text-green-600"><?= $stats['international_orders'] ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Использование ресурсов</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Водителей</span>
                                <span class="font-bold text-blue-600"><?= $stats['drivers_used'] ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Транспорта</span>
                                <span class="font-bold text-purple-600"><?= $stats['vehicles_used'] ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Перевозчиков</span>
                                <span class="font-bold text-green-600"><?= $stats['active_carriers'] ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Таблицы детальной аналитики -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Статистика по перевозчикам -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Топ перевозчики</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Перевозчик</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Заказы</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Рейтинг</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach (array_slice($carrierStats, 0, 10) as $carrier): ?>
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($carrier['carrier_name']) ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">
                                            <?= $carrier['orders_count'] ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">
                                            <div class="flex items-center">
                                                <span class="text-yellow-400 mr-1">★</span>
                                                <?= number_format($carrier['rating'], 1) ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Статистика по водителям -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Топ водители</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Водитель</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Заказы</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Перевозчик</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($driverStats as $driver): ?>
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($driver['driver_name']) ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">
                                            <?= $driver['orders_count'] ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">
                                            <?= htmlspecialchars($driver['carrier_name']) ?>
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
    </div>

    <script>
    // Функция для экспорта в CSV
    function exportToCSV() {
        const data = [
            ['Метрика', 'Значение'],
            ['Всего заказов', '<?= $stats['total_orders'] ?>'],
            ['Общие затраты', '<?= $stats['total_costs'] ?> ₸'],
            ['Средняя стоимость', '<?= number_format($stats['avg_cost'], 0) ?> ₸'],
            ['Новые заказы', '<?= $stats['new_orders'] ?>'],
            ['В работе', '<?= $stats['in_progress_orders'] ?>'],
            ['Завершенные', '<?= $stats['completed_orders'] ?>']
        ];
        
        const csvContent = data.map(row => row.join(',')).join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'crm_report_<?= date('Y-m-d') ?>.csv';
        a.click();
    }
    </script>
</body>
</html>