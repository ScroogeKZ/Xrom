<!-- Top bar -->
<div class="bg-white shadow-sm border-b border-gray-200">
    <div class="flex items-center justify-between h-16 px-4">
        <div class="flex items-center">
            <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <h1 class="ml-4 text-2xl font-semibold text-gray-900">
                <?php 
                $titles = [
                    'crm_dashboard.php' => 'Панель управления',
                    'crm_orders.php' => 'Управление заказами',
                    'crm_carriers.php' => 'Управление перевозчиками',
                    'crm_vehicles.php' => 'Управление транспортом',
                    'crm_drivers.php' => 'Управление водителями',
                    'crm_analytics.php' => 'Аналитика и отчеты',
                    'crm_calendar.php' => 'Календарь доставок',
                    'crm_settings.php' => 'Настройки системы'
                ];
                echo $titles[basename($_SERVER['PHP_SELF'])] ?? 'CRM Панель';
                ?>
            </h1>
        </div>
        
        <div class="flex items-center space-x-4">
            <div class="relative">
                <button class="bg-gray-100 p-2 rounded-full text-gray-600 hover:text-gray-900">
                    <i class="fas fa-bell"></i>
                </button>
                <?php
                // Получаем количество новых заказов для уведомлений
                if (isset($shipmentOrder)) {
                    $newOrdersCount = count($shipmentOrder->getByStatus('new'));
                    if ($newOrdersCount > 0):
                ?>
                <span class="absolute -top-1 -right-1 h-4 w-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">
                    <?php echo $newOrdersCount; ?>
                </span>
                <?php endif; } ?>
            </div>
            
            <div class="flex items-center space-x-2">
                <div class="h-8 w-8 bg-blue-500 rounded-full flex items-center justify-center">
                    <span class="text-white text-sm font-medium">A</span>
                </div>
                <span class="text-sm font-medium text-gray-700">Администратор</span>
                <a href="/admin/logout.php" class="text-gray-500 hover:text-red-600">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>
</div>