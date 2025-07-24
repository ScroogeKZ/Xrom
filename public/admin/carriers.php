<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;
use App\Models\Carrier;
use App\Models\Vehicle;
use App\Models\Driver;

// Проверка авторизации
if (!Auth::isAuthenticated()) {
    header('Location: /admin/login.php');
    exit;
}

$carrier = new Carrier();
$vehicle = new Vehicle();
$driver = new Driver();
$message = '';
$messageType = '';

// Обработка POST запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'create_carrier':
                $data = [
                    'company_name' => $_POST['company_name'],
                    'contact_person' => $_POST['contact_person'],
                    'phone' => $_POST['phone'],
                    'email' => $_POST['email'],
                    'address' => $_POST['address'],
                    'license_number' => $_POST['license_number'],
                    'rating' => $_POST['rating'] ?? 5.00,
                    'status' => $_POST['status'] ?? 'active'
                ];
                $carrier->create($data);
                $message = 'Перевозчик успешно добавлен';
                $messageType = 'success';
                break;
                
            case 'update_carrier':
                $id = $_POST['id'];
                $data = [
                    'company_name' => $_POST['company_name'],
                    'contact_person' => $_POST['contact_person'],
                    'phone' => $_POST['phone'],
                    'email' => $_POST['email'],
                    'address' => $_POST['address'],
                    'license_number' => $_POST['license_number'],
                    'rating' => $_POST['rating'],
                    'status' => $_POST['status']
                ];
                $carrier->update($id, $data);
                $message = 'Данные перевозчика обновлены';
                $messageType = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = 'Ошибка: ' . $e->getMessage();
        $messageType = 'error';
    }
}

$carriers = $carrier->getAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление перевозчиками - Хром-KZ Админ</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="bg-white shadow-sm mb-6">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-800">Управление перевозчиками</h1>
                <nav class="flex space-x-4">
                    <a href="/admin/panel.php" class="text-gray-600 hover:text-gray-800">Главная</a>
                    <a href="/admin/orders_enhanced.php" class="text-gray-600 hover:text-gray-800">Заказы</a>
                    <a href="/admin/carriers.php" class="text-gray-800 font-medium">Перевозчики</a>
                    <a href="/admin/vehicles.php" class="text-gray-600 hover:text-gray-800">Транспорт</a>
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
            <!-- Статистика перевозчиков -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Статистика</h3>
                <?php
                $active = count(array_filter($carriers, fn($c) => $c['status'] === 'active'));
                $total = count($carriers);
                ?>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Всего перевозчиков:</span>
                        <span class="font-medium"><?php echo $total; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-green-600">Активные:</span>
                        <span class="font-medium text-green-600"><?php echo $active; ?></span>
                    </div>
                </div>
            </div>

            <!-- Быстрые действия -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Быстрые действия</h3>
                <div class="space-y-2">
                    <button onclick="showAddCarrierModal()" class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">
                        Добавить перевозчика
                    </button>
                    <a href="/admin/vehicles.php" class="w-full bg-green-600 text-white py-2 px-4 rounded hover:bg-green-700 text-center block">
                        Управление транспортом
                    </a>
                </div>
            </div>

            <!-- Рейтинги -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Лучшие перевозчики</h3>
                <?php
                $topCarriers = array_slice(
                    array_filter($carriers, fn($c) => $c['status'] === 'active'),
                    0, 3
                );
                usort($topCarriers, fn($a, $b) => $b['rating'] <=> $a['rating']);
                ?>
                <div class="space-y-2">
                    <?php foreach ($topCarriers as $top): ?>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600"><?php echo htmlspecialchars(substr($top['company_name'], 0, 20)); ?></span>
                        <span class="text-sm font-medium text-yellow-600">★ <?php echo $top['rating']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Контакты -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Быстрые контакты</h3>
                <div class="space-y-2">
                    <?php foreach (array_slice($carriers, 0, 3) as $contact): ?>
                    <div class="text-sm">
                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars(substr($contact['company_name'], 0, 20)); ?></div>
                        <div class="text-gray-600"><?php echo htmlspecialchars($contact['phone']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Список перевозчиков -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-xl font-semibold text-gray-800">Список перевозчиков</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Компания</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Контактное лицо</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Телефон</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Лицензия</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Рейтинг</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($carriers as $c): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo $c['id']; ?></td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($c['company_name']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($c['contact_person']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($c['phone']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($c['license_number']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <span class="text-yellow-600">★ <?php echo $c['rating']; ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    <?php echo $c['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo $c['status'] === 'active' ? 'Активен' : 'Неактивен'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div class="flex space-x-2">
                                    <button onclick="editCarrier(<?php echo htmlspecialchars(json_encode($c)); ?>)" 
                                            class="text-blue-600 hover:text-blue-800">Изменить</button>
                                    <button onclick="viewCarrierDetails(<?php echo $c['id']; ?>)" 
                                            class="text-green-600 hover:text-green-800">Детали</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Модальное окно добавления/редактирования перевозчика -->
    <div id="carrierModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-4 max-h-screen overflow-y-auto">
            <h3 id="modalTitle" class="text-lg font-semibold mb-4">Добавить перевозчика</h3>
            <form id="carrierForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="create_carrier">
                <input type="hidden" name="id" id="carrierId">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Название компании</label>
                        <input type="text" name="company_name" id="companyName" required 
                               class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Контактное лицо</label>
                        <input type="text" name="contact_person" id="contactPerson" 
                               class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Телефон</label>
                        <input type="tel" name="phone" id="carrierPhone" required 
                               class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" id="carrierEmail" 
                               class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Номер лицензии</label>
                        <input type="text" name="license_number" id="licenseNumber" 
                               class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Адрес</label>
                        <textarea name="address" id="carrierAddress" rows="2" 
                                  class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Рейтинг</label>
                        <select name="rating" id="carrierRating" 
                                class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                            <option value="5.0">5.0 - Отлично</option>
                            <option value="4.5">4.5 - Хорошо</option>
                            <option value="4.0">4.0 - Удовлетворительно</option>
                            <option value="3.5">3.5 - Ниже среднего</option>
                            <option value="3.0">3.0 - Плохо</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Статус</label>
                        <select name="status" id="carrierStatus" 
                                class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                            <option value="active">Активен</option>
                            <option value="inactive">Неактивен</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-4 mt-6">
                    <button type="button" onclick="closeCarrierModal()" 
                            class="px-4 py-2 text-gray-600 border rounded hover:bg-gray-50">Отмена</button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAddCarrierModal() {
            document.getElementById('modalTitle').textContent = 'Добавить перевозчика';
            document.getElementById('formAction').value = 'create_carrier';
            document.getElementById('carrierForm').reset();
            document.getElementById('carrierId').value = '';
            document.getElementById('carrierModal').classList.remove('hidden');
        }

        function editCarrier(carrier) {
            document.getElementById('modalTitle').textContent = 'Редактировать перевозчика';
            document.getElementById('formAction').value = 'update_carrier';
            document.getElementById('carrierId').value = carrier.id;
            document.getElementById('companyName').value = carrier.company_name;
            document.getElementById('contactPerson').value = carrier.contact_person;
            document.getElementById('carrierPhone').value = carrier.phone;
            document.getElementById('carrierEmail').value = carrier.email;
            document.getElementById('carrierAddress').value = carrier.address;
            document.getElementById('licenseNumber').value = carrier.license_number;
            document.getElementById('carrierRating').value = carrier.rating;
            document.getElementById('carrierStatus').value = carrier.status;
            document.getElementById('carrierModal').classList.remove('hidden');
        }

        function closeCarrierModal() {
            document.getElementById('carrierModal').classList.add('hidden');
        }

        function viewCarrierDetails(carrierId) {
            window.location.href = '/admin/carrier_details.php?id=' + carrierId;
        }

        // Закрытие модального окна по клику вне его
        document.getElementById('carrierModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCarrierModal();
            }
        });
    </script>
</body>
</html>