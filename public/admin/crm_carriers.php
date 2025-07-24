<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Auth;
use App\Models\Carrier;

// Проверка авторизации
if (!Auth::isAuthenticated()) {
    header('Location: /admin/login.php');
    exit;
}

$carrier = new Carrier();
$carriers = $carrier->getAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление перевозчиками - Хром-KZ CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50" x-data="{ sidebarOpen: true, showModal: false, selectedCarrier: null }">
    <!-- Sidebar -->
    <?php include 'crm_sidebar.php'; ?>

    <!-- Main content -->
    <div class="flex-1 flex flex-col" :class="{ 'ml-64': sidebarOpen, 'ml-0': !sidebarOpen }">
        <!-- Top bar -->
        <?php include 'crm_topbar.php'; ?>

        <!-- Carriers content -->
        <div class="flex-1 p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Управление перевозчиками</h1>
                    <p class="text-gray-600">Управление транспортными компаниями и их данными</p>
                </div>
                <button @click="selectedCarrier = null; showModal = true" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i>Добавить перевозчика
                </button>
            </div>

            <!-- Carriers grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($carriers as $c): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                <?php echo htmlspecialchars($c['company_name']); ?>
                            </h3>
                            <div class="space-y-2 text-sm text-gray-600">
                                <div class="flex items-center">
                                    <i class="fas fa-user w-4 mr-2"></i>
                                    <?php echo htmlspecialchars($c['contact_person']); ?>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-phone w-4 mr-2"></i>
                                    <?php echo htmlspecialchars($c['contact_phone']); ?>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-envelope w-4 mr-2"></i>
                                    <?php echo htmlspecialchars($c['contact_email']); ?>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-certificate w-4 mr-2"></i>
                                    <?php echo htmlspecialchars($c['license_number']); ?>
                                </div>
                            </div>
                            
                            <div class="mt-4 flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex text-yellow-400">
                                        <?php 
                                        $rating = floatval($c['rating']);
                                        for ($i = 1; $i <= 5; $i++): 
                                        ?>
                                            <i class="fas fa-star <?php echo $i <= $rating ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="ml-2 text-sm text-gray-600"><?php echo $c['rating']; ?></span>
                                </div>
                                
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                    <?php echo $c['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo $c['status'] === 'active' ? 'Активен' : 'Неактивен'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4 flex justify-end space-x-2">
                        <button @click="selectedCarrier = <?php echo htmlspecialchars(json_encode($c)); ?>; showModal = true"
                                class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="toggleStatus(<?php echo $c['id']; ?>, '<?php echo $c['status']; ?>')"
                                class="text-<?php echo $c['status'] === 'active' ? 'red' : 'green'; ?>-600 hover:text-<?php echo $c['status'] === 'active' ? 'red' : 'green'; ?>-800">
                            <i class="fas fa-<?php echo $c['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div x-show="showModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
         x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-medium text-gray-900 mb-4" x-text="selectedCarrier ? 'Редактировать перевозчика' : 'Добавить перевозчика'"></h3>
            
            <form class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Название компании</label>
                        <input type="text" class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                               x-bind:value="selectedCarrier?.company_name || ''">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Контактное лицо</label>
                        <input type="text" class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                               x-bind:value="selectedCarrier?.contact_person || ''">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Телефон</label>
                        <input type="tel" class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                               x-bind:value="selectedCarrier?.contact_phone || ''">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                               x-bind:value="selectedCarrier?.contact_email || ''">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Номер лицензии</label>
                        <input type="text" class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                               x-bind:value="selectedCarrier?.license_number || ''">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Рейтинг</label>
                        <select class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <option value="5.0">5.0 - Отлично</option>
                            <option value="4.8">4.8 - Очень хорошо</option>
                            <option value="4.5">4.5 - Хорошо</option>
                            <option value="4.0">4.0 - Удовлетворительно</option>
                            <option value="3.5">3.5 - Ниже среднего</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Адрес</label>
                    <textarea rows="2" class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                              x-bind:value="selectedCarrier?.address || ''"></textarea>
                </div>
            </form>
            
            <div class="flex justify-end space-x-4 mt-6">
                <button @click="showModal = false" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50">
                    Отмена
                </button>
                <button class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Сохранить
                </button>
            </div>
        </div>
    </div>

    <script>
        function toggleStatus(id, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            // Here you would make an AJAX call to update status
            location.reload();
        }
    </script>
</body>
</html>