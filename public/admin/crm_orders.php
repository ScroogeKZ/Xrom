<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;
use App\Models\ShipmentOrder;
use App\Models\Driver;
use App\Models\Carrier;
use App\Models\Vehicle;

// Проверка авторизации
if (!Auth::isAuthenticated()) {
    header('Location: /admin/login.php');
    exit;
}

$shipmentOrder = new ShipmentOrder();
$driver = new Driver();
$carrier = new Carrier();
$vehicle = new Vehicle();

// Получаем фильтры
$statusFilter = $_GET['status'] ?? '';
$searchQuery = $_GET['search'] ?? '';

// Получаем заказы с фильтрацией
if ($statusFilter) {
    if ($statusFilter === 'in_progress') {
        $orders = array_merge(
            $shipmentOrder->getByStatus('assigned'),
            $shipmentOrder->getByStatus('picked_up'),
            $shipmentOrder->getByStatus('in_transit')
        );
    } else {
        $orders = $shipmentOrder->getByStatus($statusFilter);
    }
} else {
    $orders = $shipmentOrder->getAll();
}

// Применяем поиск
if ($searchQuery) {
    $orders = array_filter($orders, function($order) use ($searchQuery) {
        return stripos($order['contact_name'], $searchQuery) !== false ||
               stripos($order['pickup_address'], $searchQuery) !== false ||
               stripos($order['cargo_type'], $searchQuery) !== false;
    });
}

$drivers = $driver->getAll();
$carriers = $carrier->getAll();
$vehicles = $vehicle->getAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление заказами - Хром-KZ CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50" x-data="{ sidebarOpen: true, showAssignModal: false, selectedOrder: null }">
    <!-- Sidebar -->
    <?php include 'crm_sidebar.php'; ?>

    <!-- Main content -->
    <div class="flex-1 flex flex-col" :class="{ 'ml-64': sidebarOpen, 'ml-0': !sidebarOpen }">
        <!-- Top bar -->
        <?php include 'crm_topbar.php'; ?>

        <!-- Orders content -->
        <div class="flex-1 p-6">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Управление заказами</h1>
                <p class="text-gray-600">Просмотр и управление всеми заказами доставки</p>
            </div>

            <!-- Filters -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-4">
                            <!-- Search -->
                            <div class="relative">
                                <input type="text" id="searchInput" placeholder="Поиск по имени, адресу или грузу..." 
                                       value="<?php echo htmlspecialchars($searchQuery); ?>"
                                       class="w-full sm:w-80 pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                            </div>
                            
                            <!-- Status filter -->
                            <select id="statusFilter" class="border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Все статусы</option>
                                <option value="new" <?php echo $statusFilter === 'new' ? 'selected' : ''; ?>>Новые</option>
                                <option value="assigned" <?php echo $statusFilter === 'assigned' ? 'selected' : ''; ?>>Назначены</option>
                                <option value="in_progress" <?php echo $statusFilter === 'in_progress' ? 'selected' : ''; ?>>В работе</option>
                                <option value="delivered" <?php echo $statusFilter === 'delivered' ? 'selected' : ''; ?>>Доставлены</option>
                            </select>
                        </div>
                        
                        <div class="mt-3 sm:mt-0">
                            <button onclick="window.location.href='/astana.php'" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                <i class="fas fa-plus mr-2"></i>Создать заказ
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders table -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Клиент</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Маршрут</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Груз</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Назначено</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дата</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($orders as $order): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    #<?php echo $order['id']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($order['contact_name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($order['contact_phone']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">От: <?php echo htmlspecialchars(substr($order['pickup_address'], 0, 30)) . '...'; ?></div>
                                    <?php if ($order['delivery_address']): ?>
                                    <div class="text-sm text-gray-500">До: <?php echo htmlspecialchars(substr($order['delivery_address'], 0, 30)) . '...'; ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($order['cargo_type']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo $order['weight']; ?> кг</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php 
                                    $hasAssigned = false;
                                    
                                    // Поиск водителя
                                    if (!empty($order['driver_id'])) {
                                        $orderDriver = array_filter($drivers, fn($d) => $d['id'] == $order['driver_id']);
                                        if (!empty($orderDriver)) {
                                            $driverName = reset($orderDriver)['name'];
                                            echo '<div class="text-sm text-gray-900"><i class="fas fa-user mr-1"></i>' . htmlspecialchars($driverName) . '</div>';
                                            $hasAssigned = true;
                                        }
                                    }
                                    
                                    // Поиск транспорта
                                    if (!empty($order['vehicle_id'])) {
                                        $orderVehicle = array_filter($vehicles, fn($v) => $v['id'] == $order['vehicle_id']);
                                        if (!empty($orderVehicle)) {
                                            $vehicleNumber = reset($orderVehicle)['license_plate'];
                                            echo '<div class="text-sm text-gray-500"><i class="fas fa-truck mr-1"></i>' . htmlspecialchars($vehicleNumber) . '</div>';
                                            $hasAssigned = true;
                                        }
                                    }
                                    
                                    // Поиск перевозчика
                                    if (!empty($order['carrier_id'])) {
                                        $orderCarrier = array_filter($carriers, fn($c) => $c['id'] == $order['carrier_id']);
                                        if (!empty($orderCarrier)) {
                                            $carrierName = reset($orderCarrier)['name'];
                                            echo '<div class="text-sm text-gray-500"><i class="fas fa-building mr-1"></i>' . htmlspecialchars($carrierName) . '</div>';
                                            $hasAssigned = true;
                                        }
                                    }
                                    
                                    if (!$hasAssigned) {
                                        echo '<span class="text-sm text-gray-400">Не назначено</span>';
                                    }
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                        <?php 
                                        switch($order['status']) {
                                            case 'new': echo 'bg-blue-100 text-blue-800'; break;
                                            case 'assigned': echo 'bg-yellow-100 text-yellow-800'; break;
                                            case 'picked_up': echo 'bg-orange-100 text-orange-800'; break;
                                            case 'in_transit': echo 'bg-purple-100 text-purple-800'; break;
                                            case 'delivered': echo 'bg-green-100 text-green-800'; break;
                                            default: echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php 
                                        switch($order['status']) {
                                            case 'new': echo 'Новый'; break;
                                            case 'assigned': echo 'Назначен'; break;
                                            case 'picked_up': echo 'Забран'; break;
                                            case 'in_transit': echo 'В пути'; break;
                                            case 'delivered': echo 'Доставлен'; break;
                                            default: echo ucfirst($order['status']);
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('d.m.Y', strtotime($order['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button @click="selectedOrder = <?php echo htmlspecialchars(json_encode($order)); ?>; showAssignModal = true"
                                                class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-user-plus"></i>
                                        </button>
                                        <button onclick="viewOrder(<?php echo $order['id']; ?>)" 
                                                class="text-green-600 hover:text-green-900">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick="editOrder(<?php echo $order['id']; ?>)" 
                                                class="text-yellow-600 hover:text-yellow-900">
                                            <i class="fas fa-edit"></i>
                                        </button>
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

    <!-- Assignment modal -->
    <div x-show="showAssignModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
         x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Назначить ресурсы на заказ</h3>
                <div x-show="selectedOrder">
                    <p class="text-sm text-gray-600 mb-4">
                        Заказ #<span x-text="selectedOrder?.id"></span> - 
                        <span x-text="selectedOrder?.contact_name"></span>
                    </p>
                    
                    <form id="assignForm" class="space-y-4">
                        <input type="hidden" id="orderId" x-bind:value="selectedOrder?.id">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Перевозчик</label>
                            <select id="carrierId" class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Выберите перевозчика</option>
                                <?php foreach ($carriers as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['company_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Транспорт</label>
                            <select id="vehicleId" class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Выберите транспорт</option>
                                <?php foreach ($vehicles as $v): ?>
                                    <?php if ($v['status'] === 'available'): ?>
                                    <option value="<?php echo $v['id']; ?>"><?php echo htmlspecialchars($v['vehicle_number'] . ' - ' . $v['vehicle_type']); ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Водитель</label>
                            <select id="driverId" class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Выберите водителя</option>
                                <?php foreach ($drivers as $d): ?>
                                    <?php if ($d['status'] === 'available'): ?>
                                    <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name'] . ' (' . ($d['company_name'] ?? 'Без перевозчика') . ')'); ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                
                <div class="flex justify-end space-x-4 mt-6">
                    <button @click="showAssignModal = false" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50">
                        Отмена
                    </button>
                    <button onclick="assignResources()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Назначить
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Search and filter functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            updateUrl();
        });
        
        document.getElementById('statusFilter').addEventListener('change', function() {
            updateUrl();
        });
        
        function updateUrl() {
            const search = document.getElementById('searchInput').value;
            const status = document.getElementById('statusFilter').value;
            
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (status) params.append('status', status);
            
            window.location.href = '/admin/crm_orders.php?' + params.toString();
        }
        
        function assignResources() {
            const orderId = document.getElementById('orderId').value;
            const carrierId = document.getElementById('carrierId').value;
            const vehicleId = document.getElementById('vehicleId').value;
            const driverId = document.getElementById('driverId').value;
            
            // Here you would make an AJAX call to assign resources
            // For now, just reload the page
            location.reload();
        }
        
        function viewOrder(id) {
            window.open('/admin/order_details.php?id=' + id, '_blank');
        }
        
        function editOrder(id) {
            window.location.href = '/admin/edit_order.php?id=' + id;
        }
    </script>
</body>
</html>