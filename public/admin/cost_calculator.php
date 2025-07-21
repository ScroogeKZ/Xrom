<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\ShipmentOrder;
use App\Auth;

Auth::requireAuth();

$calculation = null;
$error = '';

// Калькулятор стоимости доставки
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderType = $_POST['order_type'] ?? '';
    $weight = floatval($_POST['weight'] ?? 0);
    $distance = intval($_POST['distance'] ?? 0);
    $cargoType = $_POST['cargo_type'] ?? '';
    $urgent = isset($_POST['urgent']);
    
    try {
        // Базовые тарифы
        $baseCost = 0;
        
        if ($orderType === 'astana') {
            $baseCost = 2000; // Базовая стоимость по Астане
            if ($weight > 10) {
                $baseCost += ($weight - 10) * 100; // 100₸ за каждый кг свыше 10
            }
        } elseif ($orderType === 'regional') {
            $baseCost = 5000; // Базовая стоимость межгород
            $baseCost += $distance * 8; // 8₸ за км
            if ($weight > 20) {
                $baseCost += ($weight - 20) * 150; // 150₸ за каждый кг свыше 20
            }
        }
        
        // Надбавки за тип груза
        $cargoMultiplier = match($cargoType) {
            'Стеклянные душевые кабины', 'Зеркальные панно' => 1.3, // Хрупкие +30%
            'Лифтовые порталы', 'Т-образные профили' => 1.2, // Тяжелые +20%
            'Электроника' => 1.15, // Ценные +15%
            'Документы', 'Образцы' => 0.8, // Легкие -20%
            default => 1.0
        };
        
        $baseCost *= $cargoMultiplier;
        
        // Срочность +50%
        if ($urgent) {
            $baseCost *= 1.5;
        }
        
        $calculation = [
            'base_cost' => round($baseCost),
            'weight_factor' => $weight,
            'distance_factor' => $distance,
            'cargo_multiplier' => $cargoMultiplier,
            'urgent_applied' => $urgent,
            'final_cost' => round($baseCost)
        ];
        
    } catch (Exception $e) {
        $error = 'Ошибка расчета: ' . $e->getMessage();
    }
}

$currentUser = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Калькулятор стоимости - Хром-KZ Логистика</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-3">
                <div class="flex items-center space-x-3">
                    <img src="/assets/logo.png" alt="Хром-KZ" class="h-6 w-6" onerror="this.style.display='none'">
                    <div>
                        <h1 class="text-lg font-medium text-gray-900">Калькулятор стоимости</h1>
                    </div>
                </div>
                <div class="flex space-x-4">
                    <a href="/admin/panel.php" class="text-sm text-gray-600 hover:text-gray-900">Заказы</a>
                    <a href="/admin/dashboard.php" class="text-sm text-gray-600 hover:text-gray-900">Дашборд</a>
                    <a href="/admin/reports.php" class="text-sm text-gray-600 hover:text-gray-900">Отчеты</a>
                    <a href="/admin/logistics_calendar.php" class="text-sm text-gray-600 hover:text-gray-900">Календарь</a>
                    <a href="/admin/quick_actions.php" class="text-sm text-gray-600 hover:text-gray-900">Быстрые действия</a>
                    <a href="/admin/users.php" class="text-sm text-gray-600 hover:text-gray-900">Пользователи</a>
                    <a href="/admin/search.php" class="text-sm text-gray-600 hover:text-gray-900">Поиск</a>
                    <a href="/" class="text-sm text-gray-600 hover:text-gray-900">Главная</a>
                    <a href="/admin/logout.php" class="text-sm text-gray-900 hover:text-red-600">Выйти</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-xl font-medium text-gray-900">Калькулятор стоимости доставки</h1>
                <a href="/admin/panel.php" class="bg-gray-900 text-white px-4 py-2 text-sm hover:bg-gray-800">
                    Назад в панель
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Форма расчета -->
                <div class="bg-white p-4 border border-gray-200">
                    <h2 class="text-sm font-medium text-gray-900 mb-3">Параметры доставки</h2>
                    
                    <?php if ($error): ?>
                    <div class="bg-red-50 text-red-700 px-4 py-3 mb-4 border border-red-200">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Тип доставки</label>
                            <select name="order_type" required class="w-full border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-gray-900">
                                <option value="">Выберите тип</option>
                                <option value="astana" <?php echo ($_POST['order_type'] ?? '') === 'astana' ? 'selected' : ''; ?>>
                                    По Астане
                                </option>
                                <option value="regional" <?php echo ($_POST['order_type'] ?? '') === 'regional' ? 'selected' : ''; ?>>
                                    Межгород
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Вес груза (кг)</label>
                            <input type="number" name="weight" step="0.1" value="<?php echo htmlspecialchars($_POST['weight'] ?? ''); ?>" 
                                   required class="w-full border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-gray-900">
                        </div>

                        <div id="distanceField" style="display: none;">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Расстояние (км, только для межгорода)</label>
                            <input type="number" name="distance" value="<?php echo htmlspecialchars($_POST['distance'] ?? ''); ?>" 
                                   class="w-full border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-gray-900">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Тип груза</label>
                            <select name="cargo_type" required class="w-full border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-gray-900">
                                <option value="">Выберите тип груза</option>
                                <option value="Лифтовые порталы" <?php echo ($_POST['cargo_type'] ?? '') === 'Лифтовые порталы' ? 'selected' : ''; ?>>Лифтовые порталы</option>
                                <option value="Т-образные профили" <?php echo ($_POST['cargo_type'] ?? '') === 'Т-образные профили' ? 'selected' : ''; ?>>Т-образные профили</option>
                                <option value="Металлические плинтуса" <?php echo ($_POST['cargo_type'] ?? '') === 'Металлические плинтуса' ? 'selected' : ''; ?>>Металлические плинтуса</option>
                                <option value="Корзины для кондиционеров" <?php echo ($_POST['cargo_type'] ?? '') === 'Корзины для кондиционеров' ? 'selected' : ''; ?>>Корзины для кондиционеров</option>
                                <option value="Декоративные решетки" <?php echo ($_POST['cargo_type'] ?? '') === 'Декоративные решетки' ? 'selected' : ''; ?>>Декоративные решетки</option>
                                <option value="Стеклянные душевые кабины" <?php echo ($_POST['cargo_type'] ?? '') === 'Стеклянные душевые кабины' ? 'selected' : ''; ?>>Стеклянные душевые кабины</option>
                                <option value="Зеркальные панно" <?php echo ($_POST['cargo_type'] ?? '') === 'Зеркальные панно' ? 'selected' : ''; ?>>Зеркальные панно</option>
                                <option value="Электроника" <?php echo ($_POST['cargo_type'] ?? '') === 'Электроника' ? 'selected' : ''; ?>>Электроника</option>
                                <option value="Документы" <?php echo ($_POST['cargo_type'] ?? '') === 'Документы' ? 'selected' : ''; ?>>Документы</option>
                                <option value="Образцы" <?php echo ($_POST['cargo_type'] ?? '') === 'Образцы' ? 'selected' : ''; ?>>Образцы</option>
                                <option value="Другое" <?php echo ($_POST['cargo_type'] ?? '') === 'Другое' ? 'selected' : ''; ?>>Другое</option>
                            </select>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" name="urgent" id="urgent" <?php echo isset($_POST['urgent']) ? 'checked' : ''; ?>
                                   class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                            <label for="urgent" class="ml-2 text-sm text-gray-700">Срочная доставка (+50%)</label>
                        </div>

                        <button type="submit" class="w-full bg-blue-600 text-white py-3 px-4 font-medium hover:bg-blue-700 focus:outline-none">
                            Рассчитать стоимость
                        </button>
                    </form>
                </div>

                <!-- Результат расчета -->
                <div class="bg-white p-4 border border-gray-200">
                    <h2 class="text-sm font-medium text-gray-900 mb-3">Результат расчета</h2>
                    
                    <?php if ($calculation): ?>
                    <div class="space-y-4">
                        <div class="bg-green-50 border border-green-200 p-4">
                            <div class="text-2xl font-bold text-green-800 mb-2">
                                <?php echo number_format($calculation['final_cost'], 0, ',', ' '); ?> ₸
                            </div>
                            <div class="text-sm text-green-600">Итоговая стоимость доставки</div>
                        </div>

                        <div class="space-y-3">
                            <h3 class="font-medium text-gray-900">Детализация расчета:</h3>
                            
                            <div class="text-sm space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Базовая стоимость:</span>
                                    <span class="font-medium"><?php echo number_format($calculation['base_cost'] / ($calculation['cargo_multiplier'] * ($calculation['urgent_applied'] ? 1.5 : 1)), 0, ',', ' '); ?> ₸</span>
                                </div>
                                
                                <?php if ($calculation['cargo_multiplier'] != 1.0): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Коэффициент груза:</span>
                                    <span class="font-medium">×<?php echo $calculation['cargo_multiplier']; ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($calculation['urgent_applied']): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Срочность:</span>
                                    <span class="font-medium text-red-600">+50%</span>
                                </div>
                                <?php endif; ?>
                                
                                <div class="border-t pt-2">
                                    <div class="flex justify-between font-medium">
                                        <span>Итого:</span>
                                        <span><?php echo number_format($calculation['final_cost'], 0, ',', ' '); ?> ₸</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 space-y-2">
                            <button onclick="copyToClipboard('<?php echo $calculation['final_cost']; ?>')" 
                                    class="w-full bg-gray-100 text-gray-700 py-2 px-4 text-xs hover:bg-gray-200">
                                Скопировать сумму
                            </button>
                            <button onclick="openNewOrderForm()" 
                                    class="w-full bg-blue-600 text-white py-2 px-4 text-xs hover:bg-blue-700">
                                Создать заказ с этой стоимостью
                            </button>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-gray-400 py-6">
                        <div class="text-xs">Заполните форму для расчета стоимости</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Справочная информация -->
            <div class="mt-6 bg-white p-4 border border-gray-200">
                <h2 class="text-sm font-medium text-gray-900 mb-3">Тарифы и правила расчета</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-medium text-gray-900 mb-3">Базовые тарифы:</h3>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li>• <strong>По Астане:</strong> 2,000 ₸ + 100₸/кг (свыше 10 кг)</li>
                            <li>• <strong>Межгород:</strong> 5,000 ₸ + 8₸/км + 150₸/кг (свыше 20 кг)</li>
                        </ul>
                    </div>
                    
                    <div>
                        <h3 class="font-medium text-gray-900 mb-3">Коэффициенты по типу груза:</h3>
                        <ul class="space-y-1 text-sm text-gray-600">
                            <li>• <strong>Хрупкие</strong> (стекло, зеркала): +30%</li>
                            <li>• <strong>Тяжелые</strong> (металл, профили): +20%</li>
                            <li>• <strong>Ценные</strong> (электроника): +15%</li>
                            <li>• <strong>Легкие</strong> (документы): -20%</li>
                            <li>• <strong>Срочные</strong> заказы: +50%</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Показать поле расстояния для межгорода
    document.querySelector('select[name="order_type"]').addEventListener('change', function() {
        const distanceField = document.getElementById('distanceField');
        if (this.value === 'regional') {
            distanceField.style.display = 'block';
            distanceField.querySelector('input').required = true;
        } else {
            distanceField.style.display = 'none';
            distanceField.querySelector('input').required = false;
        }
    });

    // Проверить при загрузке страницы
    if (document.querySelector('select[name="order_type"]').value === 'regional') {
        document.getElementById('distanceField').style.display = 'block';
    }

    function copyToClipboard(amount) {
        navigator.clipboard.writeText(amount).then(function() {
            alert('Сумма ' + new Intl.NumberFormat('ru-RU').format(amount) + ' ₸ скопирована в буфер обмена');
        });
    }

    function openNewOrderForm() {
        if (confirm('Перейти к форме создания заказа?')) {
            window.open('/astana.php', '_blank');
        }
    }
    </script>
</body>
</html>