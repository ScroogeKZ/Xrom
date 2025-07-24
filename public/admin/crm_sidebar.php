<!-- Sidebar -->
<div class="fixed inset-y-0 left-0 z-30 w-64 bg-white shadow-lg transform transition-transform duration-300 ease-in-out" :class="{ '-translate-x-full': !sidebarOpen }">
    <!-- Logo -->
    <div class="flex items-center justify-center h-16 px-4 bg-blue-600">
        <img src="/assets/logo.png" alt="Хром-KZ" class="h-8 w-auto">
        <span class="ml-2 text-xl font-bold text-white">Хром-KZ CRM</span>
    </div>
    
    <!-- Navigation -->
    <nav class="mt-5 px-2">
        <div class="space-y-1">
            <a href="/admin/crm_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'crm_dashboard.php' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-50'; ?> group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                <i class="fas fa-tachometer-alt mr-3 <?php echo basename($_SERVER['PHP_SELF']) === 'crm_dashboard.php' ? 'text-blue-500' : 'text-gray-400'; ?>"></i>
                Дашборд
            </a>
            
            <div class="pt-4">
                <p class="px-2 text-xs font-semibold text-gray-600 uppercase tracking-wider">Заказы</p>
                <a href="/admin/crm_orders.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'crm_orders.php' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-50'; ?> group flex items-center px-2 py-2 text-sm font-medium rounded-md mt-1">
                    <i class="fas fa-box mr-3 <?php echo basename($_SERVER['PHP_SELF']) === 'crm_orders.php' ? 'text-blue-500' : 'text-gray-400'; ?>"></i>
                    Все заказы
                </a>
                <a href="/admin/crm_orders.php?status=new" class="text-gray-700 hover:bg-gray-50 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                    <i class="fas fa-plus-circle mr-3 text-gray-400"></i>
                    Новые заказы
                </a>
                <a href="/admin/crm_orders.php?status=in_progress" class="text-gray-700 hover:bg-gray-50 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                    <i class="fas fa-truck mr-3 text-gray-400"></i>
                    В работе
                </a>
            </div>
            
            <div class="pt-4">
                <p class="px-2 text-xs font-semibold text-gray-600 uppercase tracking-wider">Ресурсы</p>
                <a href="/admin/crm_carriers.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'crm_carriers.php' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-50'; ?> group flex items-center px-2 py-2 text-sm font-medium rounded-md mt-1">
                    <i class="fas fa-building mr-3 <?php echo basename($_SERVER['PHP_SELF']) === 'crm_carriers.php' ? 'text-blue-500' : 'text-gray-400'; ?>"></i>
                    Перевозчики
                </a>
                <a href="/admin/crm_vehicles.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'crm_vehicles.php' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-50'; ?> group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                    <i class="fas fa-car mr-3 <?php echo basename($_SERVER['PHP_SELF']) === 'crm_vehicles.php' ? 'text-blue-500' : 'text-gray-400'; ?>"></i>
                    Транспорт
                </a>
                <a href="/admin/crm_drivers.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'crm_drivers.php' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-50'; ?> group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                    <i class="fas fa-users mr-3 <?php echo basename($_SERVER['PHP_SELF']) === 'crm_drivers.php' ? 'text-blue-500' : 'text-gray-400'; ?>"></i>
                    Водители
                </a>
            </div>
            
            <div class="pt-4">
                <p class="px-2 text-xs font-semibold text-gray-600 uppercase tracking-wider">Аналитика</p>
                <a href="/admin/crm_analytics.php" class="text-gray-700 hover:bg-gray-50 group flex items-center px-2 py-2 text-sm font-medium rounded-md mt-1">
                    <i class="fas fa-chart-bar mr-3 text-gray-400"></i>
                    Отчеты
                </a>
                <a href="/admin/crm_calendar.php" class="text-gray-700 hover:bg-gray-50 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                    <i class="fas fa-calendar mr-3 text-gray-400"></i>
                    Календарь
                </a>
            </div>
            
            <div class="pt-4">
                <p class="px-2 text-xs font-semibold text-gray-600 uppercase tracking-wider">Система</p>
                <a href="/admin/crm_settings.php" class="text-gray-700 hover:bg-gray-50 group flex items-center px-2 py-2 text-sm font-medium rounded-md mt-1">
                    <i class="fas fa-cog mr-3 text-gray-400"></i>
                    Настройки
                </a>
            </div>
        </div>
    </nav>
</div>