<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;
use App\Models\Vehicle;
use App\Models\Carrier;

// Проверка авторизации
if (!Auth::isAuthenticated()) {
    header('Location: /admin/login.php');
    exit;
}

$vehicle = new Vehicle();
$carrier = new Carrier();
$message = '';
$messageType = '';

// Обработка POST запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'create_vehicle':
                $data = [
                    'carrier_id' => $_POST['carrier_id'],
                    'vehicle_number' => $_POST['vehicle_number'],
                    'vehicle_type' => $_POST['vehicle_type'],
                    'brand' => $_POST['brand'],
                    'model' => $_POST['model'],
                    'year' => $_POST['year'],
                    'capacity_weight' => $_POST['capacity_weight'],
                    'capacity_volume' => $_POST['capacity_volume'],
                    'fuel_type' => $_POST['fuel_type'],
                    'status' => $_POST['status'] ?? 'available',
                    'insurance_expires' => $_POST['insurance_expires'] ?: null,
                    'tech_inspection_expires' => $_POST['tech_inspection_expires'] ?: null
                ];
                $vehicle->create($data);
                $message = 'Транспортное средство успешно добавлено';
                $messageType = 'success';
                break;
                
            case 'update_vehicle':
                $id = $_POST['id'];
                $data = [
                    'carrier_id' => $_POST['carrier_id'],
                    'vehicle_number' => $_POST['vehicle_number'],
                    'vehicle_type' => $_POST['vehicle_type'],
                    'brand' => $_POST['brand'],
                    'model' => $_POST['model'],
                    'year' => $_POST['year'],
                    'capacity_weight' => $_POST['capacity_weight'],
                    'capacity_volume' => $_POST['capacity_volume'],
                    'fuel_type' => $_POST['fuel_type'],
                    'status' => $_POST['status'],
                    'insurance_expires' => $_POST['insurance_expires'] ?: null,
                    'tech_inspection_expires' => $_POST['tech_inspection_expires'] ?: null
                ];
                $vehicle->update($id, $data);
                $message = 'Данные транспортного средства обновлены';
                $messageType = 'success';
                break;
                
            case 'update_status':
                $id = $_POST['id'];
                $status = $_POST['status'];
                $vehicle->updateStatus($id, $status);
                $message = 'Статус транспортного средства обновлен';
                $messageType = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = 'Ошибка: ' . $e->getMessage();
        $messageType = 'error';
    }
}

$vehicles = $vehicle->getAll();
$carriers = $carrier->getActiveCarriers();
$vehicleTypes = $vehicle->getVehicleTypes();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление транспортом - Хром-KZ Админ</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="bg-white shadow-sm mb-6">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-800">Управление транспортом</h1>
                <nav class="flex space-x-4">
                    <a href="/admin/panel.php" class="text-gray-600 hover:text-gray-800">Главная</a>
                    <a href="/admin/orders_enhanced.php" class="text-gray-600 hover:text-gray-800">Заказы</a>
                    <a href="/admin/carriers.php" class="text-gray-600 hover:text-gray-800">Перевозчики</a>
                    <a href="/admin/vehicles.php" class="text-gray-800 font-medium">Транспорт</a>
                    <a href="/admin/drivers.php" class="text-gray-600 hover:text-gray-800">Водители</a>
                    <a href="/admin/logout.php" class="text-red-600 hover:text-red-800">Выход</a>
                </nav>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4">
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
            <!-- Статистика транспорта -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Статистика</h3>
                <?php
                $available = count(array_filter($vehicles, fn($v) => $v['status'] === 'available'));
                $busy = count(array_filter($vehicles, fn($v) => $v['status'] === 'busy'));
                $total = count($vehicles);
                ?>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Всего единиц:</span>
                        <span class="font-medium"><?php echo $total; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-green-600">Свободно:</span>
                        <span class="font-medium text-green-600"><?php echo $available; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-orange-600">Занято:</span>
                        <span class="font-medium text-orange-600"><?php echo $busy; ?></span>
                    </div>
                </div>
            </div>

            <!-- Быстрые действия -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Быстрые действия</h3>
                <div class="space-y-2">
                    <button onclick="showAddVehicleModal()" class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">
                        Добавить транспорт
                    </button>
                    <a href="/admin/carriers.php" class="w-full bg-green-600 text-white py-2 px-4 rounded hover:bg-green-700 text-center block">
                        Управление перевозчиками
                    </a>
                </div>
            </div>

            <!-- Типы транспорта -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">По типам</h3>
                <?php
                $typeStats = [];
                foreach ($vehicles as $v) {
                    $type = $v['vehicle_type'];
                    $typeStats[$type] = ($typeStats[$type] ?? 0) + 1;
                }
                ?>
                <div class="space-y-2">
                    <?php foreach (array_slice($typeStats, 0, 4) as $type => $count): ?>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600"><?php echo $type; ?></span>
                        <span class="text-sm font-medium"><?php echo $count; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Фильтры -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Фильтры</h3>
                <div class="space-y-2">
                    <select id="statusFilter" onchange="filterVehicles()" class="w-full p-2 border rounded">
                        <option value="">Все статусы</option>
                        <option value="available">Свободные</option>
                        <option value="busy">Заняты</option>
                        <option value="maintenance">На обслуживании</option>
                    </select>
                    <select id="carrierFilter" onchange="filterVehicles()" class="w-full p-2 border rounded">
                        <option value="">Все перевозчики</option>
                        <?php foreach ($carriers as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['company_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Таблица транспорта -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-xl font-semibold text-gray-800">Список транспортных средств</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Номер</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Тип</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Марка/Модель</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Перевозчик</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Грузоподъемность</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200" id="vehiclesTable">
                        <?php foreach ($vehicles as $v): ?>
                        <tr data-status="<?php echo $v['status']; ?>" data-carrier="<?php echo $v['carrier_id']; ?>">
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo $v['id']; ?></td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($v['vehicle_number']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($v['vehicle_type']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php echo htmlspecialchars($v['brand'] . ' ' . $v['model']); ?>
                                <div class="text-xs text-gray-500"><?php echo $v['year']; ?> г.</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($v['company_name'] ?? 'Не указан'); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php echo number_format($v['capacity_weight'], 0); ?> кг
                                <div class="text-xs text-gray-500"><?php echo $v['capacity_volume']; ?> м³</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    <?php 
                                    switch($v['status']) {
                                        case 'available': echo 'bg-green-100 text-green-800'; break;
                                        case 'busy': echo 'bg-orange-100 text-orange-800'; break;
                                        case 'maintenance': echo 'bg-red-100 text-red-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php 
                                    switch($v['status']) {
                                        case 'available': echo 'Свободен'; break;
                                        case 'busy': echo 'Занят'; break;
                                        case 'maintenance': echo 'Обслуживание'; break;
                                        default: echo ucfirst($v['status']);
                                    }
                                    ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div class="flex space-x-2">
                                    <button onclick="editVehicle(<?php echo htmlspecialchars(json_encode($v)); ?>)" 
                                            class="text-blue-600 hover:text-blue-800">Изменить</button>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="id" value="<?php echo $v['id']; ?>">
                                        <input type="hidden" name="status" value="<?php echo $v['status'] === 'available' ? 'maintenance' : 'available'; ?>">
                                        <button type="submit" class="text-orange-600 hover:text-orange-800">
                                            <?php echo $v['status'] === 'available' ? 'На обслуживание' : 'В работу'; ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Модальное окно добавления/редактирования транспорта -->
    <div id="vehicleModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-4xl mx-4 max-h-screen overflow-y-auto">
            <h3 id="modalTitle" class="text-lg font-semibold mb-4">Добавить транспортное средство</h3>
            <form id="vehicleForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="create_vehicle">
                <input type="hidden" name="id" id="vehicleId">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Перевозчик</label>
                        <select name="carrier_id" id="carrierId" required class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                            <option value="">Выберите перевозчика</option>
                            <?php foreach ($carriers as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['company_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Гос. номер</label>
                        <input type="text" name="vehicle_number" id="vehicleNumber" required 
                               placeholder="A123BC01" class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Тип транспорта</label>
                        <select name="vehicle_type" id="vehicleType" required class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                            <option value="">Выберите тип</option>
                            <?php foreach ($vehicleTypes as $key => $value): ?>
                                <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Марка</label>
                        <input type="text" name="brand" id="vehicleBrand" 
                               class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Модель</label>
                        <input type="text" name="model" id="vehicleModel" 
                               class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Год выпуска</label>
                        <input type="number" name="year" id="vehicleYear" min="1990" max="2025" 
                               class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Грузоподъемность (кг)</label>
                        <input type="number" name="capacity_weight" id="capacityWeight" step="0.01" 
                               class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Объем (м³)</label>
                        <input type="number" name="capacity_volume" id="capacityVolume" step="0.01" 
                               class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Тип топлива</label>
                        <select name="fuel_type" id="fuelType" class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                            <option value="">Выберите тип</option>
                            <option value="Бензин">Бензин</option>
                            <option value="Дизель">Дизель</option>
                            <option value="Газ">Газ</option>
                            <option value="Электро">Электричество</option>
                            <option value="Гибрид">Гибрид</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Страховка до</label>
                        <input type="date" name="insurance_expires" id="insuranceExpires" 
                               class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Техосмотр до</label>
                        <input type="date" name="tech_inspection_expires" id="techInspectionExpires" 
                               class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Статус</label>
                        <select name="status" id="vehicleStatus" class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                            <option value="available">Свободен</option>
                            <option value="busy">Занят</option>
                            <option value="maintenance">На обслуживании</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-4 mt-6">
                    <button type="button" onclick="closeVehicleModal()" 
                            class="px-4 py-2 text-gray-600 border rounded hover:bg-gray-50">Отмена</button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAddVehicleModal() {
            document.getElementById('modalTitle').textContent = 'Добавить транспортное средство';
            document.getElementById('formAction').value = 'create_vehicle';
            document.getElementById('vehicleForm').reset();
            document.getElementById('vehicleId').value = '';
            document.getElementById('vehicleModal').classList.remove('hidden');
        }

        function editVehicle(vehicle) {
            document.getElementById('modalTitle').textContent = 'Редактировать транспортное средство';
            document.getElementById('formAction').value = 'update_vehicle';
            document.getElementById('vehicleId').value = vehicle.id;
            document.getElementById('carrierId').value = vehicle.carrier_id;
            document.getElementById('vehicleNumber').value = vehicle.vehicle_number;
            document.getElementById('vehicleType').value = vehicle.vehicle_type;
            document.getElementById('vehicleBrand').value = vehicle.brand;
            document.getElementById('vehicleModel').value = vehicle.model;
            document.getElementById('vehicleYear').value = vehicle.year;
            document.getElementById('capacityWeight').value = vehicle.capacity_weight;
            document.getElementById('capacityVolume').value = vehicle.capacity_volume;
            document.getElementById('fuelType').value = vehicle.fuel_type;
            document.getElementById('insuranceExpires').value = vehicle.insurance_expires;
            document.getElementById('techInspectionExpires').value = vehicle.tech_inspection_expires;
            document.getElementById('vehicleStatus').value = vehicle.status;
            document.getElementById('vehicleModal').classList.remove('hidden');
        }

        function closeVehicleModal() {
            document.getElementById('vehicleModal').classList.add('hidden');
        }

        function filterVehicles() {
            const status = document.getElementById('statusFilter').value;
            const carrier = document.getElementById('carrierFilter').value;
            const rows = document.querySelectorAll('#vehiclesTable tr');
            
            rows.forEach(row => {
                let show = true;
                
                if (status && row.dataset.status !== status) {
                    show = false;
                }
                
                if (carrier && row.dataset.carrier !== carrier) {
                    show = false;
                }
                
                row.style.display = show ? '' : 'none';
            });
        }

        // Закрытие модального окна по клику вне его
        document.getElementById('vehicleModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeVehicleModal();
            }
        });
    </script>
</body>
</html>