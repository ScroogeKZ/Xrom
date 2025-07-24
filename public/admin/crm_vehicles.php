<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;
use App\Models\Vehicle;

// Проверка авторизации
if (!Auth::isAuthenticated()) {
    header('Location: /admin/login.php');
    exit;
}

$vehicle = new Vehicle();
$vehicles = $vehicle->getAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление транспортом - Хром-KZ CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50" x-data="{ sidebarOpen: true, statusFilter: 'all' }">
    <!-- Sidebar -->
    <?php include 'crm_sidebar.php'; ?>

    <!-- Main content -->
    <div class="flex-1 flex flex-col" :class="{ 'ml-64': sidebarOpen, 'ml-0': !sidebarOpen }">
        <!-- Top bar -->
        <?php include 'crm_topbar.php'; ?>

        <!-- Vehicles content -->
        <div class="flex-1 p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Управление транспортом</h1>
                    <p class="text-gray-600">Управление автопарком и техническими данными</p>
                </div>
                <div class="flex space-x-3">
                    <select x-model="statusFilter" class="border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="all">Все статусы</option>
                        <option value="available">Свободные</option>
                        <option value="busy">Занятые</option>
                    </select>
                    <button class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>Добавить транспорт
                    </button>
                </div>
            </div>

            <!-- Vehicles grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($vehicles as $v): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden" 
                     x-show="statusFilter === 'all' || statusFilter === '<?php echo $v['status']; ?>'">
                    <!-- Status header -->
                    <div class="px-6 py-3 bg-<?php echo $v['status'] === 'available' ? 'green' : 'red'; ?>-50 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($v['vehicle_number']); ?></span>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                <?php echo $v['status'] === 'available' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $v['status'] === 'available' ? 'Свободен' : 'Занят'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Vehicle info -->
                    <div class="p-6">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500">Тип:</span>
                                <span class="text-sm text-gray-900"><?php echo htmlspecialchars($v['vehicle_type']); ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500">Марка:</span>
                                <span class="text-sm text-gray-900"><?php echo htmlspecialchars($v['brand'] . ' ' . $v['model']); ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500">Год:</span>
                                <span class="text-sm text-gray-900"><?php echo $v['year']; ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500">Грузоподъемность:</span>
                                <span class="text-sm text-gray-900"><?php echo number_format($v['capacity_weight'], 0); ?> кг</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500">Объем:</span>
                                <span class="text-sm text-gray-900"><?php echo $v['capacity_volume']; ?> м³</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500">Топливо:</span>
                                <span class="text-sm text-gray-900"><?php echo htmlspecialchars($v['fuel_type']); ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500">Перевозчик:</span>
                                <span class="text-sm text-gray-900"><?php echo htmlspecialchars($v['company_name']); ?></span>
                            </div>
                        </div>
                        
                        <!-- Action buttons -->
                        <div class="mt-6 flex justify-end space-x-2">
                            <button class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="toggleVehicleStatus(<?php echo $v['id']; ?>, '<?php echo $v['status']; ?>')"
                                    class="text-<?php echo $v['status'] === 'available' ? 'red' : 'green'; ?>-600 hover:text-<?php echo $v['status'] === 'available' ? 'red' : 'green'; ?>-800">
                                <i class="fas fa-<?php echo $v['status'] === 'available' ? 'pause' : 'play'; ?>"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Summary stats -->
            <div class="mt-8 bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Статистика автопарка</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <?php
                    $totalVehicles = count($vehicles);
                    $availableVehicles = count(array_filter($vehicles, fn($v) => $v['status'] === 'available'));
                    $busyVehicles = $totalVehicles - $availableVehicles;
                    $totalCapacity = array_sum(array_column($vehicles, 'capacity_weight'));
                    ?>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600"><?php echo $totalVehicles; ?></div>
                        <div class="text-sm text-gray-500">Всего единиц</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600"><?php echo $availableVehicles; ?></div>
                        <div class="text-sm text-gray-500">Свободно</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-600"><?php echo $busyVehicles; ?></div>
                        <div class="text-sm text-gray-500">Занято</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600"><?php echo number_format($totalCapacity/1000, 1); ?></div>
                        <div class="text-sm text-gray-500">Общая грузоподъемность (т)</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleVehicleStatus(id, currentStatus) {
            const newStatus = currentStatus === 'available' ? 'busy' : 'available';
            // Here you would make an AJAX call to update status
            location.reload();
        }
    </script>
</body>
</html>