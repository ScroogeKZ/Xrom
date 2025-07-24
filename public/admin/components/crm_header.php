<?php
use App\CRM\CRMAuth;

$currentUser = CRMAuth::getCurrentUser();
$userPermissions = $currentUser['permissions'] ?? [];
$isAdmin = $currentUser['is_admin'] ?? false;
?>

<!-- Верхняя панель CRM -->
<div class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
    <div class="flex items-center justify-between">
        <!-- Логотип и название -->
        <div class="flex items-center space-x-4">
            <img src="/assets/logo.png" alt="Хром-KZ" class="h-8 w-8">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Хром-KZ CRM</h1>
                <p class="text-sm text-gray-500">Система управления логистикой</p>
            </div>
        </div>

        <!-- Правая часть с уведомлениями и профилем -->
        <div class="flex items-center space-x-4">
            <!-- Уведомления -->
            <div class="relative">
                <button class="p-2 text-gray-400 hover:text-gray-600 relative">
                    <i class="fas fa-bell text-lg"></i>
                    <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">3</span>
                </button>
            </div>

            <!-- Профиль пользователя -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-100">
                    <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                        <span class="text-white text-sm font-medium">
                            <?= strtoupper(substr($currentUser['first_name'] ?? 'A', 0, 1)) ?>
                        </span>
                    </div>
                    <div class="hidden md:block text-left">
                        <div class="text-sm font-medium text-gray-900">
                            <?= htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?>
                        </div>
                        <div class="text-xs text-gray-500">
                            <?= htmlspecialchars($currentUser['position'] ?? 'Сотрудник') ?>
                        </div>
                    </div>
                    <i class="fas fa-chevron-down text-gray-400"></i>
                </button>

                <!-- Выпадающее меню -->
                <div x-show="open" @click.away="open = false" x-transition
                     class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                    <a href="/admin/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-user mr-2"></i>Профиль
                    </a>
                    <a href="/admin/settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-cog mr-2"></i>Настройки
                    </a>
                    <?php if ($isAdmin): ?>
                    <a href="/admin/users.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-users mr-2"></i>Пользователи
                    </a>
                    <?php endif; ?>
                    <div class="border-t border-gray-200 my-1"></div>
                    <a href="/admin/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                        <i class="fas fa-sign-out-alt mr-2"></i>Выйти
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>