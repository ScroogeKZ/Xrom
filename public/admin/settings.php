<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\CRM\CRMAuth;
use App\CRM\RoleManager;

// Проверка авторизации и прав доступа
CRMAuth::requireCRMAuth('settings', 'read');

$roleManager = new RoleManager();
$currentUser = CRMAuth::getCurrentUser();

// Обработка сохранения настроек
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $companyName = $_POST['company_name'] ?? '';
    $companyAddress = $_POST['company_address'] ?? '';
    $companyPhone = $_POST['company_phone'] ?? '';
    $companyEmail = $_POST['company_email'] ?? '';
    $timeZone = $_POST['timezone'] ?? 'Asia/Almaty';
    $currency = $_POST['currency'] ?? 'KZT';
    $language = $_POST['language'] ?? 'ru';
    
    try {
        // Создаем или обновляем настройки системы
        $settings = [
            'company_name' => $companyName,
            'company_address' => $companyAddress,
            'company_phone' => $companyPhone,
            'company_email' => $companyEmail,
            'timezone' => $timeZone,
            'currency' => $currency,
            'language' => $language,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $currentUser['id']
        ];
        
        // Сохраняем в файл настроек (можно также в БД)
        file_put_contents(__DIR__ . '/../../config/settings.json', json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        $success = "Настройки успешно сохранены";
    } catch (Exception $e) {
        $error = "Ошибка при сохранении настроек: " . $e->getMessage();
    }
}

// Загрузка текущих настроек
$settingsFile = __DIR__ . '/../../config/settings.json';
$settings = [];
if (file_exists($settingsFile)) {
    $settings = json_decode(file_get_contents($settingsFile), true) ?: [];
}

// Значения по умолчанию
$settings = array_merge([
    'company_name' => 'Хром-KZ Логистика',
    'company_address' => 'г. Астана, ул. Республики 15',
    'company_phone' => '+7 (7172) 12-34-56',
    'company_email' => 'info@chrome-kz.com',
    'timezone' => 'Asia/Almaty',
    'currency' => 'KZT',
    'language' => 'ru'
], $settings);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки системы - CRM Хром-KZ</title>
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
                <div class="max-w-4xl mx-auto">
                    <!-- Заголовок -->
                    <div class="mb-6">
                        <h1 class="text-2xl font-bold text-gray-900">Настройки системы</h1>
                        <p class="text-gray-600">Конфигурация основных параметров CRM системы</p>
                    </div>

                    <!-- Уведомления -->
                    <?php if (isset($success)): ?>
                    <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?= htmlspecialchars($success) ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error)): ?>
                    <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Системная информация -->
                        <div class="lg:col-span-1">
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    Системная информация
                                </h3>
                                
                                <div class="space-y-3">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Версия системы:</span>
                                        <span class="font-medium">CRM v2.0</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">PHP версия:</span>
                                        <span class="font-medium"><?= phpversion() ?></span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">База данных:</span>
                                        <span class="font-medium">PostgreSQL</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Время сервера:</span>
                                        <span class="font-medium"><?= date('d.m.Y H:i:s') ?></span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Часовой пояс:</span>
                                        <span class="font-medium"><?= date_default_timezone_get() ?></span>
                                    </div>
                                </div>

                                <!-- Статистика пользователей -->
                                <div class="mt-6 pt-6 border-t border-gray-200">
                                    <h4 class="text-sm font-semibold text-gray-900 mb-3">Активность системы</h4>
                                    <?php
                                    $stmt = $roleManager->db->prepare("SELECT COUNT(*) as total FROM users WHERE is_active = true");
                                    $stmt->execute();
                                    $activeUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                                    
                                    $stmt = $roleManager->db->prepare("SELECT COUNT(*) as total FROM shipment_orders WHERE created_at >= CURRENT_DATE");
                                    $stmt->execute();
                                    $todayOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                                    ?>
                                    <div class="space-y-2">
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Активных пользователей:</span>
                                            <span class="font-medium text-green-600"><?= $activeUsers ?></span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Заказов сегодня:</span>
                                            <span class="font-medium text-blue-600"><?= $todayOrders ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Настройки -->
                        <div class="lg:col-span-2">
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                                <div class="p-6 border-b border-gray-200">
                                    <h2 class="text-lg font-semibold text-gray-900">Основные настройки</h2>
                                </div>
                                
                                <form method="POST" class="p-6" x-data="{ activeTab: 'company' }">
                                    <!-- Вкладки -->
                                    <div class="border-b border-gray-200 mb-6">
                                        <nav class="flex space-x-8">
                                            <button type="button" @click="activeTab = 'company'" 
                                                    :class="activeTab === 'company' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                                                    class="py-2 px-1 border-b-2 font-medium text-sm">
                                                <i class="fas fa-building mr-2"></i>
                                                Компания
                                            </button>
                                            <button type="button" @click="activeTab = 'system'" 
                                                    :class="activeTab === 'system' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                                                    class="py-2 px-1 border-b-2 font-medium text-sm">
                                                <i class="fas fa-cogs mr-2"></i>
                                                Система
                                            </button>
                                            <button type="button" @click="activeTab = 'security'" 
                                                    :class="activeTab === 'security' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                                                    class="py-2 px-1 border-b-2 font-medium text-sm">
                                                <i class="fas fa-shield-alt mr-2"></i>
                                                Безопасность
                                            </button>
                                        </nav>
                                    </div>

                                    <!-- Вкладка: Компания -->
                                    <div x-show="activeTab === 'company'" class="space-y-6">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Название компании</label>
                                                <input type="text" name="company_name" value="<?= htmlspecialchars($settings['company_name']) ?>" 
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                                       <?= CRMAuth::can('settings', 'update') ? '' : 'readonly' ?>>
                                            </div>
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Email компании</label>
                                                <input type="email" name="company_email" value="<?= htmlspecialchars($settings['company_email']) ?>" 
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                                       <?= CRMAuth::can('settings', 'update') ? '' : 'readonly' ?>>
                                            </div>
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Телефон</label>
                                                <input type="tel" name="company_phone" value="<?= htmlspecialchars($settings['company_phone']) ?>" 
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                                       <?= CRMAuth::can('settings', 'update') ? '' : 'readonly' ?>>
                                            </div>
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Валюта</label>
                                                <select name="currency" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                                        <?= CRMAuth::can('settings', 'update') ? '' : 'disabled' ?>>
                                                    <option value="KZT" <?= $settings['currency'] === 'KZT' ? 'selected' : '' ?>>Тенге (KZT)</option>
                                                    <option value="USD" <?= $settings['currency'] === 'USD' ? 'selected' : '' ?>>Доллар США (USD)</option>
                                                    <option value="EUR" <?= $settings['currency'] === 'EUR' ? 'selected' : '' ?>>Евро (EUR)</option>
                                                    <option value="RUB" <?= $settings['currency'] === 'RUB' ? 'selected' : '' ?>>Рубль (RUB)</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Адрес компании</label>
                                            <textarea name="company_address" rows="3" 
                                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                                      <?= CRMAuth::can('settings', 'update') ? '' : 'readonly' ?>><?= htmlspecialchars($settings['company_address']) ?></textarea>
                                        </div>
                                    </div>

                                    <!-- Вкладка: Система -->
                                    <div x-show="activeTab === 'system'" class="space-y-6">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Часовой пояс</label>
                                                <select name="timezone" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                                        <?= CRMAuth::can('settings', 'update') ? '' : 'disabled' ?>>
                                                    <option value="Asia/Almaty" <?= $settings['timezone'] === 'Asia/Almaty' ? 'selected' : '' ?>>Алматы (UTC+6)</option>
                                                    <option value="Asia/Nur-Sultan" <?= $settings['timezone'] === 'Asia/Nur-Sultan' ? 'selected' : '' ?>>Астана (UTC+6)</option>
                                                    <option value="Europe/Moscow" <?= $settings['timezone'] === 'Europe/Moscow' ? 'selected' : '' ?>>Москва (UTC+3)</option>
                                                    <option value="UTC" <?= $settings['timezone'] === 'UTC' ? 'selected' : '' ?>>UTC</option>
                                                </select>
                                            </div>
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Язык системы</label>
                                                <select name="language" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                                        <?= CRMAuth::can('settings', 'update') ? '' : 'disabled' ?>>
                                                    <option value="ru" <?= $settings['language'] === 'ru' ? 'selected' : '' ?>>Русский</option>
                                                    <option value="kk" <?= $settings['language'] === 'kk' ? 'selected' : '' ?>>Қазақша</option>
                                                    <option value="en" <?= $settings['language'] === 'en' ? 'selected' : '' ?>>English</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Настройки уведомлений -->
                                        <div class="border-t border-gray-200 pt-6">
                                            <h4 class="text-sm font-semibold text-gray-900 mb-4">Уведомления</h4>
                                            <div class="space-y-3">
                                                <label class="flex items-center">
                                                    <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" checked>
                                                    <span class="ml-2 text-sm text-gray-700">Email уведомления о новых заказах</span>
                                                </label>
                                                <label class="flex items-center">
                                                    <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" checked>
                                                    <span class="ml-2 text-sm text-gray-700">SMS уведомления клиентам</span>
                                                </label>
                                                <label class="flex items-center">
                                                    <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                    <span class="ml-2 text-sm text-gray-700">Telegram уведомления</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Вкладка: Безопасность -->
                                    <div x-show="activeTab === 'security'" class="space-y-6">
                                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                            <div class="flex">
                                                <i class="fas fa-exclamation-triangle text-yellow-400 mr-3 mt-1"></i>
                                                <div>
                                                    <h4 class="text-sm font-semibold text-yellow-800">Настройки безопасности</h4>
                                                    <p class="text-sm text-yellow-700 mt-1">Изменение настроек безопасности требует перезагрузки системы</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="space-y-4">
                                            <div class="flex items-center justify-between py-3 border-b border-gray-200">
                                                <div>
                                                    <h4 class="text-sm font-medium text-gray-900">Двухфакторная аутентификация</h4>
                                                    <p class="text-sm text-gray-500">Требовать 2FA для всех администраторов</p>
                                                </div>
                                                <button type="button" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-gray-200 transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                    <span class="translate-x-0 inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                                </button>
                                            </div>

                                            <div class="flex items-center justify-between py-3 border-b border-gray-200">
                                                <div>
                                                    <h4 class="text-sm font-medium text-gray-900">Автоматический выход</h4>
                                                    <p class="text-sm text-gray-500">Автоматический выход после бездействия (24 часа)</p>
                                                </div>
                                                <button type="button" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-blue-600 transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                    <span class="translate-x-5 inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                                </button>
                                            </div>

                                            <div class="flex items-center justify-between py-3">
                                                <div>
                                                    <h4 class="text-sm font-medium text-gray-900">Журнал действий</h4>
                                                    <p class="text-sm text-gray-500">Ведение детального журнала всех действий пользователей</p>
                                                </div>
                                                <button type="button" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-blue-600 transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                    <span class="translate-x-5 inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Кнопки действий -->
                                    <?php if (CRMAuth::can('settings', 'update')): ?>
                                    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                                        <button type="button" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                                            Сбросить
                                        </button>
                                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                            <i class="fas fa-save mr-2"></i>
                                            Сохранить настройки
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>