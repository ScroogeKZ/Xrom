<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\ShipmentOrder;

$success = false;
$error = '';

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
        
        $data = [
            'order_type' => 'astana',
            'pickup_address' => $_POST['pickup_address'] ?? '',
            'ready_time' => $_POST['ready_time'] ?? '',
            'contact_name' => $_POST['contact_name'] ?? '',
            'contact_phone' => $_POST['contact_phone'] ?? '',
            'cargo_type' => $_POST['cargo_type'] ?? '',
            'weight' => $_POST['weight'] ?? '',
            'dimensions' => $_POST['dimensions'] ?? '',
            'delivery_address' => $_POST['delivery_address'] ?? '',
            'recipient_contact' => $_POST['recipient_contact'] ?? '',
            'recipient_phone' => $_POST['recipient_phone'] ?? '',
            'notes' => $_POST['notes'] ?? '',
            'comment' => $_POST['comment'] ?? '',
            'photo_path' => $photoPath
        ];
        
        $result = $shipmentOrder->create($data);
        if ($result) {
            $success = true;
        }
    } catch (Exception $e) {
        $error = 'Ошибка при создании заказа: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Доставка по Астане - Хром-KZ Логистика</title>
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
        .gradient-text {
            background: linear-gradient(135deg, #1e40af, #f59e0b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white/95 backdrop-blur-sm shadow-xl sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <div class="bg-gradient-to-br from-primary to-primary-dark p-2 rounded-lg">
                        <img src="/assets/logo.png" alt="Хром-KZ" class="h-8 w-8 filter brightness-0 invert" onerror="this.style.display='none'">
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold gradient-text">Хром-KZ</h1>
                        <p class="text-sm text-gray-600 font-medium">Логистика</p>
                    </div>
                </div>
                <div class="flex space-x-4">
                    <a href="/" class="text-gray-600 hover:text-primary font-medium px-4 py-2 rounded-xl hover:bg-gray-100 transition-all duration-200">Главная</a>
                    <a href="/regional.php" class="text-gray-600 hover:text-primary font-medium px-4 py-2 rounded-xl hover:bg-gray-100 transition-all duration-200">Межгородские заказы</a>
                    <a href="/admin/login.php" class="text-gray-600 hover:text-primary font-medium px-4 py-2 rounded-xl hover:bg-gray-100 transition-all duration-200">Панель управления</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-12">
        <div class="bg-white rounded-3xl shadow-2xl p-10 border border-gray-100">
            <div class="text-center mb-8">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">
                    <span class="gradient-text">Создание заказа по Астане</span>
                </h1>
                <p class="text-gray-600 text-lg">Заполните форму для создания заявки на доставку в пределах города</p>
            </div>
            
            <?php if ($success): ?>
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-accent text-green-800 px-6 py-4 rounded-xl mb-8 shadow-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <span class="text-2xl">✅</span>
                        </div>
                        <div class="ml-3">
                            <p class="font-semibold">Заказ успешно создан!</p>
                            <p class="text-sm">Автоматическое уведомление отправлено ответственным сотрудникам.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-gradient-to-r from-red-50 to-pink-50 border-l-4 border-red-500 text-red-800 px-6 py-4 rounded-xl mb-8 shadow-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <span class="text-2xl">❌</span>
                        </div>
                        <div class="ml-3">
                            <p class="font-semibold">Произошла ошибка</p>
                            <p class="text-sm"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-8">
                <div class="grid lg:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-800 mb-3">
                            <span class="flex items-center">
                                📍 Адрес забора груза *
                            </span>
                        </label>
                        <input type="text" name="pickup_address" required 
                               class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 text-lg"
                               placeholder="Укажите точный адрес">
                    </div>
                    
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-800 mb-3">
                            <span class="flex items-center">
                                🎯 Адрес доставки *
                            </span>
                        </label>
                        <input type="text" name="delivery_address" required 
                               class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 text-lg"
                               placeholder="Укажите точный адрес">
                    </div>
                </div>
                
                <div class="grid lg:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-800 mb-3">
                            <span class="flex items-center">
                                ⏰ Время готовности груза *
                            </span>
                        </label>
                        <input type="text" name="ready_time" required placeholder="например: 14:00"
                               class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 text-lg">
                    </div>
                    
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-800 mb-3">
                            <span class="flex items-center">
                                📦 Тип груза *
                            </span>
                        </label>
                        <select name="cargo_type" required 
                                class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 text-lg">
                            <option value="">Выберите тип груза</option>
                            <option value="лифтовые порталы">Лифтовые порталы</option>
                            <option value="т-образные профили">Т-образные профили</option>
                            <option value="металлические плинтуса">Металлические плинтуса</option>
                            <option value="корзины для кондиционеров">Корзины для кондиционеров</option>
                            <option value="декоративные решетки">Декоративные решетки</option>
                            <option value="перфорированные фасадные кассеты">Перфорированные фасадные кассеты</option>
                            <option value="стеклянные душевые кабины">Стеклянные душевые кабины</option>
                            <option value="зеркальные панно">Зеркальные панно</option>
                            <option value="рамы и багеты">Рамы и багеты</option>
                            <option value="козырьки">Козырьки</option>
                            <option value="документы">Документы</option>
                            <option value="образцы">Образцы</option>
                            <option value="другое">Другое</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid lg:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-800 mb-3">
                            <span class="flex items-center">
                                ⚖️ Вес груза (кг) *
                            </span>
                        </label>
                        <input type="number" name="weight" required min="1" placeholder="Укажите вес в килограммах"
                               class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 text-lg">
                    </div>
                    
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-800 mb-3">
                            <span class="flex items-center">
                                📐 Габариты *
                            </span>
                        </label>
                        <input type="text" name="dimensions" required placeholder="например: 30x20x10 см"
                               class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 text-lg">
                    </div>
                </div>
                
                <div class="grid lg:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-800 mb-3">
                            <span class="flex items-center">
                                👤 Контактное лицо отправителя *
                            </span>
                        </label>
                        <input type="text" name="contact_person" required placeholder="ФИО отправителя"
                               class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 text-lg">
                    </div>
                    
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-800 mb-3">
                            <span class="flex items-center">
                                📱 Телефон отправителя *
                            </span>
                        </label>
                        <input type="tel" name="phone" required placeholder="+7 (XXX) XXX-XX-XX"
                               class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 text-lg">
                    </div>
                </div>
                
                <div class="grid lg:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-800 mb-3">
                            <span class="flex items-center">
                                👥 Контактное лицо получателя *
                            </span>
                        </label>
                        <input type="text" name="recipient_contact" required placeholder="ФИО получателя"
                               class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 text-lg">
                    </div>
                    
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-800 mb-3">
                            <span class="flex items-center">
                                📞 Телефон получателя *
                            </span>
                        </label>
                        <input type="tel" name="recipient_phone" required placeholder="+7 (XXX) XXX-XX-XX"
                               class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 text-lg">
                    </div>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-800 mb-3">
                        <span class="flex items-center">
                            💬 Комментарий
                        </span>
                    </label>
                    <textarea name="comment" rows="4" placeholder="Дополнительная информация о доставке..."
                              class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 text-lg resize-none"></textarea>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-800 mb-3">
                        <span class="flex items-center">
                            📷 Фотография груза
                        </span>
                    </label>
                    <input type="file" name="photo" accept="image/*" 
                           class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 text-lg file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-primary-dark">
                    <p class="text-sm text-gray-500 mt-2">Поддерживаемые форматы: JPG, PNG, GIF. Максимальный размер: 5MB</p>
                </div>
                
                <div class="pt-6">
                    <button type="submit" class="w-full bg-gradient-to-r from-primary to-primary-dark text-white py-4 px-8 rounded-xl hover:shadow-xl transform hover:scale-105 transition-all duration-300 font-bold text-xl">
                        <span class="flex items-center justify-center">
                            📋 Создать заказ
                        </span>
                    </button>
                    <p class="text-center text-gray-500 text-sm mt-4">
                        После создания заказа автоматически будет отправлено уведомление в Telegram
                    </p>
                </div>
            </form>
        </div>
    </div>
</body>
</html>