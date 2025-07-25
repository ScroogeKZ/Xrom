<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\ShipmentOrder;
use App\EmailService;

$success = false;
$error = '';

// Function to validate international phone numbers
function validateInternationalPhone($phoneNumber) {
    $phone = preg_replace('/[^0-9+]/', '', $phoneNumber); // Remove all non-digits except +
    
    // Must start with + and have at least 7 digits
    if (preg_match('/^\+\d{7,15}$/', $phone)) {
        return true;
    }
    return false;
}

// List of popular international destinations
$countries = [
    'Россия', 'Китай', 'Турция', 'Германия', 'Узбекистан', 'Кыргызстан', 'Таджикистан',
    'Беларусь', 'Польша', 'Италия', 'Франция', 'Нидерланды', 'ОАЭ', 'Индия', 'Корея',
    'Япония', 'США', 'Канада', 'Великобритания', 'Испания', 'Чехия', 'Венгрия', 'Румыния'
];

// Delivery methods for international shipping
$delivery_methods = [
    'Авиа экспресс (1-3 дня)',
    'Авиа стандарт (3-7 дней)', 
    'Морской контейнер (15-45 дней)',
    'Автомобильный (7-14 дней)',
    'Железнодорожный (10-21 день)',
    'Курьерская служба'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $shipmentOrder = new ShipmentOrder();
        
        // Handle file upload
        $photoPath = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/';
            $fileName = uniqid() . '_' . basename($_FILES['photo']['name']);
            $uploadPath = $uploadDir . $fileName;
            
            // Check file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (in_array($_FILES['photo']['type'], $allowedTypes)) {
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
                    $photoPath = '/uploads/' . $fileName;
                }
            }
        }
        
        // Validate and format time
        $readyTime = $_POST['ready_time'] ?? '';
        if ($readyTime && !empty($readyTime)) {
            // If time doesn't contain colon, assume it's in format like "1400" and convert to "14:00"
            if (!str_contains($readyTime, ':')) {
                if (strlen($readyTime) === 3) {
                    $readyTime = '0' . substr($readyTime, 0, 1) . ':' . substr($readyTime, 1);
                } elseif (strlen($readyTime) === 4) {
                    $readyTime = substr($readyTime, 0, 2) . ':' . substr($readyTime, 2);
                }
            }
            // Validate time format
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $readyTime)) {
                throw new Exception('Неверный формат времени. Используйте формат ЧЧ:ММ (например: 14:30)');
            }
        }

        // Validate phone numbers
        $phone = $_POST['contact_phone'] ?? '';
        $recipientPhone = $_POST['recipient_phone'] ?? '';
        
        if ($phone && !empty($phone) && !validateInternationalPhone($phone)) {
            throw new Exception('Неверный формат номера телефона отправителя. Используйте международный формат +xxxxxxxxxx');
        }
        
        if ($recipientPhone && !empty($recipientPhone) && !validateInternationalPhone($recipientPhone)) {
            throw new Exception('Неверный формат номера телефона получателя. Используйте международный формат +xxxxxxxxxx');
        }

        $data = [
            'order_type' => 'international',
            'pickup_address' => $_POST['pickup_address'] ?? '',
            'ready_time' => $readyTime,
            'contact_name' => $_POST['contact_name'] ?? '',
            'contact_phone' => $_POST['contact_phone'] ?? '',
            'cargo_type' => $_POST['cargo_type'] ?? '',
            'weight' => $_POST['weight'] ?? '',
            'dimensions' => $_POST['dimensions'] ?? '',
            'pickup_city' => $_POST['pickup_city'] ?? 'Астана',
            'destination_city' => $_POST['destination_city'] ?? '',
            'delivery_address' => $_POST['delivery_address'] ?? '',
            'delivery_method' => $_POST['delivery_method'] ?? '',
            'desired_arrival_date' => !empty($_POST['desired_arrival_date']) ? $_POST['desired_arrival_date'] : null,
            'recipient_contact' => $_POST['recipient_contact'] ?? '',
            'recipient_phone' => $_POST['recipient_phone'] ?? '',
            'comment' => $_POST['comment'] ?? '',
            'customs_value' => $_POST['customs_value'] ?? '',
            'customs_description' => $_POST['customs_description'] ?? '',
            'insurance_required' => isset($_POST['insurance_required']) ? 1 : 0,
            'tracking_required' => isset($_POST['tracking_required']) ? 1 : 0
        ];

        $orderId = $shipmentOrder->create($data);
        
        if ($orderId) {
            $success = true;
            
            // Send email notification
            try {
                $emailService = new EmailService();
                $emailService->sendOrderNotification($orderId, $data, 'international');
            } catch (Exception $e) {
                error_log("Email notification failed: " . $e->getMessage());
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$cargoTypes = [
    'Лифтовые порталы',
    'Т-образные профили', 
    'Металлические плинтуса',
    'Корзины для кондиционеров',
    'Декоративные решетки',
    'Перфорированные фасадные кассеты',
    'Стеклянные душевые кабины',
    'Зеркальные панно',
    'Рамы и багеты',
    'Козырьки',
    'Документы',
    'Образцы',
    'Оборудование',
    'Другое'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Международные отгрузки - Хром-KZ Логистика</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary': '#1e40af',
                        'primary-dark': '#1e3a8a',
                        'secondary': '#f59e0b',
                        'accent': '#10b981'
                    }
                }
            }
        }
    </script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 50%, #3730a3 100%);
        }
        .form-container {
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        .input-focus:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            border-color: #3b82f6;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white/95 backdrop-blur-sm shadow-xl sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-3">
                    <div class="bg-gradient-to-br from-primary to-primary-dark p-2 rounded-lg">
                        <img src="/assets/logo.png" alt="Хром-KZ" class="h-6 w-6 md:h-8 md:w-8 filter brightness-0 invert" onerror="this.style.display='none'">
                    </div>
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold bg-gradient-to-r from-primary to-secondary bg-clip-text text-transparent">Хром-KZ</h1>
                        <p class="text-xs md:text-sm text-gray-600 font-medium">Логистика</p>
                    </div>
                </div>
                <div class="flex space-x-2 md:space-x-4">
                    <a href="/" class="text-gray-600 hover:text-primary font-medium px-3 py-2 rounded-lg hover:bg-gray-100 transition-all">
                        Главная
                    </a>
                    <a href="/admin/login.php" class="text-gray-600 hover:text-primary font-medium px-3 py-2 rounded-lg hover:bg-gray-100 transition-all">
                        Вход в систему
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="gradient-bg text-white py-12 rounded-3xl mb-8">
                <h1 class="text-3xl md:text-4xl font-bold mb-4">🌍 Международные отгрузки</h1>
                <p class="text-lg md:text-xl opacity-90 max-w-2xl mx-auto px-4">
                    Оформление заявки на международную доставку грузов
                </p>
            </div>
        </div>

        <?php if ($success): ?>
        <div class="bg-green-50 border border-green-200 text-green-800 px-6 py-4 rounded-2xl mb-8 shadow-lg">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-lg font-semibold">Заказ успешно создан!</h3>
                    <p class="text-sm mt-1">Ваша заявка на международную доставку принята в обработку. Менеджер свяжется с вами для уточнения деталей.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-800 px-6 py-4 rounded-2xl mb-8 shadow-lg">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-lg font-semibold">Ошибка при создании заказа</h3>
                    <p class="text-sm mt-1"><?php echo htmlspecialchars($error); ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Form -->
        <div class="form-container rounded-3xl p-8 md:p-12">
            <form method="POST" enctype="multipart/form-data" class="space-y-8">
                
                <!-- Pickup Information -->
                <div class="bg-blue-50 rounded-2xl p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                        📍 Информация об отправке
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Город отправления</label>
                            <select name="pickup_city" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent input-focus">
                                <option value="Астана">Астана</option>
                                <option value="Алматы">Алматы</option>
                                <option value="Шымкент">Шымкент</option>
                                <option value="Караганда">Караганда</option>
                                <option value="Другой">Другой город</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Время готовности груза</label>
                            <input type="time" name="ready_time" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent input-focus">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Адрес забора груза *</label>
                            <textarea name="pickup_address" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent input-focus" rows="3" placeholder="Укажите точный адрес забора груза"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Destination Information -->
                <div class="bg-green-50 rounded-2xl p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                        🌍 Информация о доставке
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Страна назначения *</label>
                            <select name="destination_city" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent input-focus">
                                <option value="">Выберите страну</option>
                                <?php foreach ($countries as $country): ?>
                                <option value="<?php echo htmlspecialchars($country); ?>"><?php echo htmlspecialchars($country); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Способ доставки *</label>
                            <select name="delivery_method" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent input-focus">
                                <option value="">Выберите способ доставки</option>
                                <?php foreach ($delivery_methods as $method): ?>
                                <option value="<?php echo htmlspecialchars($method); ?>"><?php echo htmlspecialchars($method); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Адрес доставки *</label>
                            <textarea name="delivery_address" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent input-focus" rows="3" placeholder="Укажите точный адрес доставки в стране назначения"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Желаемая дата прибытия</label>
                            <input type="date" name="desired_arrival_date" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent input-focus" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                        </div>
                    </div>
                </div>

                <!-- Cargo Information -->
                <div class="bg-yellow-50 rounded-2xl p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                        📦 Информация о грузе
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Тип груза *</label>
                            <select name="cargo_type" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent input-focus">
                                <option value="">Выберите тип груза</option>
                                <?php foreach ($cargoTypes as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Вес (кг)</label>
                            <input type="number" name="weight" step="0.01" min="0" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent input-focus" placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Габариты (ДxШxВ, см)</label>
                            <input type="text" name="dimensions" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent input-focus" placeholder="100x50x30">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Таможенная стоимость (USD)</label>
                            <input type="number" name="customs_value" step="0.01" min="0" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent input-focus" placeholder="0.00">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Описание для таможни</label>
                            <textarea name="customs_description" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent input-focus" rows="3" placeholder="Подробное описание груза для таможенного оформления"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="bg-purple-50 rounded-2xl p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                        👤 Контактная информация
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Контактное лицо (отправитель) *</label>
                            <input type="text" name="contact_name" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent input-focus" placeholder="ФИО отправителя">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Телефон отправителя *</label>
                            <input type="tel" name="contact_phone" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent input-focus" placeholder="+77771234567">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Контактное лицо (получатель)</label>
                            <input type="text" name="recipient_contact" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent input-focus" placeholder="ФИО получателя">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Телефон получателя</label>
                            <input type="tel" name="recipient_phone" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent input-focus" placeholder="+1234567890">
                        </div>
                    </div>
                </div>

                <!-- Additional Options -->
                <div class="bg-gray-50 rounded-2xl p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                        ⚙️ Дополнительные услуги
                    </h2>
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input type="checkbox" name="insurance_required" id="insurance" class="w-5 h-5 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2">
                            <label for="insurance" class="ml-3 text-sm font-medium text-gray-700">Требуется страхование груза</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="tracking_required" id="tracking" class="w-5 h-5 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary focus:ring-2">
                            <label for="tracking" class="ml-3 text-sm font-medium text-gray-700">Требуется отслеживание доставки</label>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Комментарии и особые требования</label>
                            <textarea name="comment" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent input-focus" rows="4" placeholder="Укажите любые особые требования или комментарии к заказу"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Фото груза</label>
                            <input type="file" name="photo" accept="image/*" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent input-focus">
                            <p class="text-xs text-gray-500 mt-1">Прикрепите фото груза для более точного расчета стоимости</p>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="text-center pt-6">
                    <button type="submit" class="bg-gradient-to-r from-primary to-primary-dark text-white px-12 py-4 rounded-2xl font-bold text-lg hover:shadow-2xl transform hover:scale-105 transition-all duration-300 min-w-[300px]">
                        🚀 Создать заказ на международную доставку
                    </button>
                    <p class="text-sm text-gray-500 mt-4">
                        После отправки заявки наш менеджер свяжется с вами для согласования деталей и стоимости доставки
                    </p>
                </div>
            </form>
        </div>

        <!-- Navigation Links -->
        <div class="mt-12 text-center">
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <a href="/astana.php" class="bg-blue-600 text-white px-6 py-3 rounded-xl font-medium hover:bg-blue-700 transition-all">
                    📋 Доставка по Астане
                </a>
                <a href="/regional.php" class="bg-green-600 text-white px-6 py-3 rounded-xl font-medium hover:bg-green-700 transition-all">
                    🗂️ Межгородские доставки
                </a>
                <a href="/tracking.php" class="bg-gray-600 text-white px-6 py-3 rounded-xl font-medium hover:bg-gray-700 transition-all">
                    📍 Отследить заказ
                </a>
            </div>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button')?.addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const phoneInputs = form.querySelectorAll('input[type="tel"]');
            
            phoneInputs.forEach(input => {
                input.addEventListener('blur', function() {
                    const phone = this.value.trim();
                    if (phone && !phone.startsWith('+')) {
                        this.setCustomValidity('Номер телефона должен начинаться с +');
                    } else {
                        this.setCustomValidity('');
                    }
                });
            });
        });
    </script>
</body>
</html>