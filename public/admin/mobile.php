<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\ShipmentOrder;
use App\Auth;

Auth::requireAuth();

$orderModel = new ShipmentOrder();

// Получаем последние заказы
$recentOrders = [];
try {
    $pdo = \Database::getInstance()->getConnection();
    $stmt = $pdo->prepare("
        SELECT id, order_type, status, pickup_address, contact_name, 
               contact_phone, created_at 
        FROM shipment_orders 
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $stmt->execute();
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recentOrders = [];
}

// Статистика
$stats = [
    'total_orders' => $orderModel->getCount(),
    'new_orders' => $orderModel->getCount(['status' => 'new']),
    'processing_orders' => $orderModel->getCount(['status' => 'processing']),
    'completed_orders' => $orderModel->getCount(['status' => 'completed'])
];

$currentUser = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Мобильная панель - Хром-KZ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Стили для мобильной версии */
        body { 
            -webkit-user-select: none;
            -webkit-touch-callout: none;
            -webkit-tap-highlight-color: transparent;
        }
        
        .mobile-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-new { background: #fef2f2; color: #dc2626; }
        .status-processing { background: #fffbeb; color: #d97706; }
        .status-completed { background: #f0fdf4; color: #059669; }
        
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid #e5e7eb;
            padding: 8px 0;
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 8px;
            text-decoration: none;
            color: #6b7280;
        }
        
        .nav-item.active { color: #1f2937; }
        
        .main-content {
            padding-bottom: 80px; /* Место для нижней навигации */
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Верхняя панель -->
    <div class="bg-white border-b border-gray-200 px-4 py-3 flex justify-between items-center">
        <div>
            <h1 class="text-lg font-semibold text-gray-900">Хром-KZ</h1>
            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($currentUser['username']); ?></p>
        </div>
        <a href="/admin/logout.php" class="text-red-600 text-sm">Выход</a>
    </div>

    <div class="main-content px-4 py-4">
        <!-- Статистика -->
        <div class="grid grid-cols-2 gap-3 mb-6">
            <div class="mobile-card p-4 text-center">
                <div class="text-2xl font-bold text-gray-900"><?php echo $stats['total_orders']; ?></div>
                <div class="text-sm text-gray-500">Всего заказов</div>
            </div>
            <div class="mobile-card p-4 text-center">
                <div class="text-2xl font-bold text-red-600"><?php echo $stats['new_orders']; ?></div>
                <div class="text-sm text-gray-500">Новые</div>
            </div>
            <div class="mobile-card p-4 text-center">
                <div class="text-2xl font-bold text-yellow-600"><?php echo $stats['processing_orders']; ?></div>
                <div class="text-sm text-gray-500">В работе</div>
            </div>
            <div class="mobile-card p-4 text-center">
                <div class="text-2xl font-bold text-green-600"><?php echo $stats['completed_orders']; ?></div>
                <div class="text-sm text-gray-500">Завершено</div>
            </div>
        </div>

        <!-- Поиск -->
        <div class="mobile-card p-4 mb-6">
            <form method="GET" action="/admin/search.php">
                <div class="flex space-x-2">
                    <input type="text" name="q" placeholder="Поиск заказов..."
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-blue-500">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm">
                        Найти
                    </button>
                </div>
            </form>
        </div>

        <!-- Быстрые действия -->
        <div class="mobile-card p-4 mb-6">
            <h3 class="font-medium text-gray-900 mb-3">Быстрые действия</h3>
            <div class="grid grid-cols-2 gap-3">
                <a href="/astana.php" class="bg-blue-50 text-blue-700 p-3 rounded-lg text-center text-sm font-medium">
                    Новый заказ по Астане
                </a>
                <a href="/regional.php" class="bg-green-50 text-green-700 p-3 rounded-lg text-center text-sm font-medium">
                    Межгород заказ
                </a>
                <a href="/admin/reports.php" class="bg-purple-50 text-purple-700 p-3 rounded-lg text-center text-sm font-medium">
                    Отчеты
                </a>
                <a href="/admin/calendar.php" class="bg-orange-50 text-orange-700 p-3 rounded-lg text-center text-sm font-medium">
                    Календарь
                </a>
            </div>
        </div>

        <!-- Последние заказы -->
        <div class="mobile-card">
            <div class="p-4 border-b border-gray-200">
                <h3 class="font-medium text-gray-900">Последние заказы</h3>
            </div>
            <div class="divide-y divide-gray-200">
                <?php foreach ($recentOrders as $order): 
                    $statusClass = 'status-' . $order['status'];
                    $statusText = match($order['status']) {
                        'new' => 'Новый',
                        'processing' => 'В работе',
                        'completed' => 'Завершен',
                        default => $order['status']
                    };
                ?>
                    <div class="p-4">
                        <div class="flex justify-between items-start mb-2">
                            <div class="font-medium text-gray-900">№<?php echo $order['id']; ?></div>
                            <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                        </div>
                        <div class="text-sm text-gray-600 mb-1">
                            <?php echo htmlspecialchars($order['contact_name'] ?? ''); ?>
                        </div>
                        <div class="text-sm text-gray-500 mb-2">
                            <?php echo htmlspecialchars(substr($order['pickup_address'] ?? '', 0, 40)); ?>
                            <?php if (strlen($order['pickup_address'] ?? '') > 40): ?>...<?php endif; ?>
                        </div>
                        <div class="flex justify-between items-center">
                            <div class="text-xs text-gray-400">
                                <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?>
                            </div>
                            <a href="/admin/panel.php?order_id=<?php echo $order['id']; ?>" 
                               class="text-blue-600 text-sm">
                                Открыть
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Нижняя навигация -->
    <div class="bottom-nav">
        <div class="grid grid-cols-5">
            <a href="/admin/mobile.php" class="nav-item active">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                </svg>
                <span class="text-xs">Главная</span>
            </a>
            <a href="/admin/panel.php" class="nav-item">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2v1a1 1 0 001 1h6a1 1 0 001-1V3a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-xs">Заказы</span>
            </a>
            <a href="/admin/search.php" class="nav-item">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-xs">Поиск</span>
            </a>
            <a href="/admin/reports.php" class="nav-item">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path>
                </svg>
                <span class="text-xs">Отчеты</span>
            </a>
            <a href="/admin/calendar.php" class="nav-item">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-xs">Календарь</span>
            </a>
        </div>
    </div>

    <script>
        // Простая мобильная навигация
        document.addEventListener('DOMContentLoaded', function() {
            // Скрыть адресную строку на мобильных устройствах
            if (/Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
                setTimeout(function() {
                    window.scrollTo(0, 1);
                }, 100);
            }
        });
    </script>
</body>
</html>