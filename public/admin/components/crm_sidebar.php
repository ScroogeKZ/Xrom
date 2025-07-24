<?php
use App\CRM\CRMAuth;

$currentPage = basename($_SERVER['PHP_SELF']);
$currentUser = CRMAuth::getCurrentUser();
$isAdmin = $currentUser['is_admin'] ?? false;

function isActiveLink($page) {
    global $currentPage;
    return $currentPage === $page ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100';
}
?>

<!-- Боковое меню CRM -->
<div class="w-64 bg-white shadow-lg h-screen fixed left-0 top-0 overflow-y-auto border-r border-gray-200">
    <!-- Логотип -->
    <div class="p-6 border-b border-gray-200">
        <div class="flex items-center space-x-3">
            <img src="/assets/logo.png" alt="Хром-KZ" class="h-10 w-10">
            <div>
                <h2 class="text-lg font-bold text-gray-900">Хром-KZ</h2>
                <p class="text-xs text-gray-500">CRM Система</p>
            </div>
        </div>
    </div>

    <!-- Навигационное меню -->
    <nav class="mt-6 px-4">
        <!-- Главная -->
        <div class="mb-6">
            <a href="/admin/crm_dashboard.php" class="flex items-center px-3 py-2 rounded-lg <?= isActiveLink('crm_dashboard.php') ?>">
                <i class="fas fa-tachometer-alt mr-3 w-5"></i>
                <span>Панель управления</span>
            </a>
        </div>

        <!-- Основные разделы -->
        <div class="mb-6">
            <h3 class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Основные</h3>
            
            <?php if (CRMAuth::can('orders', 'read')): ?>
            <a href="/admin/crm_orders.php" class="flex items-center px-3 py-2 rounded-lg <?= isActiveLink('crm_orders.php') ?> mb-1">
                <i class="fas fa-box mr-3 w-5"></i>
                <span>Заказы</span>
                <span class="ml-auto bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">12</span>
            </a>
            <?php endif; ?>

            <?php if (CRMAuth::can('carriers', 'read')): ?>
            <a href="/admin/crm_carriers.php" class="flex items-center px-3 py-2 rounded-lg <?= isActiveLink('crm_carriers.php') ?> mb-1">
                <i class="fas fa-truck mr-3 w-5"></i>
                <span>Перевозчики</span>
            </a>
            <?php endif; ?>

            <?php if (CRMAuth::can('vehicles', 'read')): ?>
            <a href="/admin/crm_vehicles.php" class="flex items-center px-3 py-2 rounded-lg <?= isActiveLink('crm_vehicles.php') ?> mb-1">
                <i class="fas fa-car mr-3 w-5"></i>
                <span>Транспорт</span>
            </a>
            <?php endif; ?>

            <?php if (CRMAuth::can('drivers', 'read')): ?>
            <a href="/admin/crm_drivers.php" class="flex items-center px-3 py-2 rounded-lg <?= isActiveLink('crm_drivers.php') ?> mb-1">
                <i class="fas fa-user-tie mr-3 w-5"></i>
                <span>Водители</span>
            </a>
            <?php endif; ?>
        </div>

        <!-- Аналитика -->
        <?php if (CRMAuth::can('reports', 'read')): ?>
        <div class="mb-6">
            <h3 class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Аналитика</h3>
            
            <a href="/admin/reports.php" class="flex items-center px-3 py-2 rounded-lg <?= isActiveLink('reports.php') ?> mb-1">
                <i class="fas fa-chart-bar mr-3 w-5"></i>
                <span>Отчеты</span>
            </a>
            
            <a href="/admin/analytics.php" class="flex items-center px-3 py-2 rounded-lg <?= isActiveLink('analytics.php') ?> mb-1">
                <i class="fas fa-chart-line mr-3 w-5"></i>
                <span>Аналитика</span>
            </a>
        </div>
        <?php endif; ?>

        <!-- Администрирование (только для супер-админов) -->
        <?php if (CRMAuth::hasRole(['super_admin'])): ?>
        <div class="mb-6" x-data="{ adminOpen: false }">
            <button @click="adminOpen = !adminOpen" class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider hover:text-gray-700">
                <span>Администрирование</span>
                <i class="fas fa-chevron-down transition-transform text-xs" :class="adminOpen ? 'rotate-180' : ''"></i>
            </button>
            
            <div x-show="adminOpen" x-transition class="mt-2 space-y-1">
                <?php if (CRMAuth::can('users', 'read')): ?>
                <a href="/admin/users.php" class="flex items-center px-6 py-2 rounded-lg <?= isActiveLink('users.php') ?> text-sm">
                    <i class="fas fa-users mr-3 w-4 text-xs"></i>
                    <span>Пользователи</span>
                </a>
                <?php endif; ?>

                <a href="/admin/roles.php" class="flex items-center px-6 py-2 rounded-lg <?= isActiveLink('roles.php') ?> text-sm">
                    <i class="fas fa-shield-alt mr-3 w-4 text-xs"></i>
                    <span>Роли и права</span>
                </a>

                <?php if (CRMAuth::can('settings', 'read')): ?>
                <a href="/admin/settings.php" class="flex items-center px-6 py-2 rounded-lg <?= isActiveLink('settings.php') ?> text-sm">
                    <i class="fas fa-cog mr-3 w-4 text-xs"></i>
                    <span>Настройки</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Дополнительно -->
        <div class="mb-6">
            <h3 class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Дополнительно</h3>
            
            <a href="/admin/profile.php" class="flex items-center px-3 py-2 rounded-lg <?= isActiveLink('profile.php') ?> mb-1">
                <i class="fas fa-user mr-3 w-5"></i>
                <span>Профиль</span>
            </a>
            
            <a href="/admin/notifications.php" class="flex items-center px-3 py-2 rounded-lg <?= isActiveLink('notifications.php') ?> mb-1">
                <i class="fas fa-bell mr-3 w-5"></i>
                <span>Уведомления</span>
            </a>
            
            <a href="/admin/help.php" class="flex items-center px-3 py-2 rounded-lg <?= isActiveLink('help.php') ?> mb-1">
                <i class="fas fa-question-circle mr-3 w-5"></i>
                <span>Справка</span>
            </a>
        </div>
    </nav>

    <!-- Информация о пользователе внизу -->
    <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200 bg-gray-50">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
                <span class="text-white text-sm font-medium">
                    <?= strtoupper(substr($currentUser['first_name'] ?? 'A', 0, 1)) ?>
                </span>
            </div>
            <div class="flex-1">
                <div class="text-sm font-medium text-gray-900">
                    <?= htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?>
                </div>
                <div class="text-xs text-gray-500">
                    <?php 
                    $roles = $currentUser['roles'] ?? [];
                    if (is_string($roles)) {
                        $roles = json_decode($roles, true) ?: [];
                    }
                    if (is_array($roles)) {
                        echo implode(', ', array_map('ucfirst', $roles));
                    } else {
                        echo 'Роль не назначена';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>