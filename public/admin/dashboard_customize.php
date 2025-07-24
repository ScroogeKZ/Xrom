<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\CRM\CRMAuth;
use App\Models\DashboardWidget;

// Проверка авторизации
CRMAuth::requireCRMAuth();

$currentUser = CRMAuth::getCurrentUser();
$widgetModel = new DashboardWidget();

// Обработка сохранения настроек
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $widgets = json_decode($_POST['widgets'] ?? '[]', true);
    if ($widgetModel->saveUserWidgets($currentUser['id'], $widgets)) {
        $success = 'Настройки виджетов сохранены';
    } else {
        $error = 'Ошибка при сохранении настроек';
    }
}

$userWidgets = $widgetModel->getUserWidgets($currentUser['id']);
$availableWidgets = $widgetModel->getAvailableWidgets();

// Если у пользователя нет настроек, создаем дефолтные виджеты
if (empty($userWidgets)) {
    $defaultWidgets = [
        ['type' => 'orders_stats', 'config' => [], 'visible' => true],
        ['type' => 'recent_orders', 'config' => ['limit' => 5], 'visible' => true],
        ['type' => 'carriers_stats', 'config' => [], 'visible' => true],
        ['type' => 'quick_actions', 'config' => [], 'visible' => true]
    ];
    $widgetModel->saveUserWidgets($currentUser['id'], $defaultWidgets);
    $userWidgets = $widgetModel->getUserWidgets($currentUser['id']);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройка дашборда - CRM Хром-KZ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
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
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Настройка дашборда</h1>
                        <p class="text-gray-600">Настройте виджеты на главной панели управления</p>
                    </div>
                    <div class="flex space-x-3">
                        <button onclick="resetToDefault()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            Сбросить
                        </button>
                        <button onclick="saveWidgets()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Сохранить
                        </button>
                    </div>
                </div>

                <?php if (isset($success)): ?>
                    <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-700 rounded-lg">
                        <?= $success ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-700 rounded-lg">
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Доступные виджеты -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">Доступные виджеты</h2>
                            <p class="text-sm text-gray-600">Перетащите виджеты в активные для добавления</p>
                        </div>
                        <div class="p-6">
                            <div id="available-widgets" class="space-y-3">
                                <?php foreach ($availableWidgets as $type => $widget): ?>
                                    <div class="widget-item p-4 border border-gray-200 rounded-lg cursor-move hover:bg-gray-50" 
                                         data-type="<?= $type ?>" data-size="<?= $widget['size'] ?>">
                                        <div class="flex items-center">
                                            <i class="fas fa-grip-vertical text-gray-400 mr-3"></i>
                                            <div>
                                                <h3 class="font-medium text-gray-900"><?= $widget['name'] ?></h3>
                                                <p class="text-sm text-gray-600"><?= $widget['description'] ?></p>
                                                <span class="inline-block mt-1 px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded">
                                                    <?= ucfirst($widget['size']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Активные виджеты -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">Активные виджеты</h2>
                            <p class="text-sm text-gray-600">Измените порядок перетаскиванием</p>
                        </div>
                        <div class="p-6">
                            <div id="active-widgets" class="space-y-3 min-h-[200px]">
                                <?php foreach ($userWidgets as $widget): ?>
                                    <div class="widget-item active-widget p-4 border border-blue-200 bg-blue-50 rounded-lg cursor-move" 
                                         data-type="<?= $widget['widget_type'] ?>" 
                                         data-config='<?= $widget['widget_config'] ?>'
                                         data-visible="<?= $widget['is_visible'] ? 'true' : 'false' ?>">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center">
                                                <i class="fas fa-grip-vertical text-gray-400 mr-3"></i>
                                                <div>
                                                    <h3 class="font-medium text-gray-900">
                                                        <?= $availableWidgets[$widget['widget_type']]['name'] ?>
                                                    </h3>
                                                    <p class="text-sm text-gray-600">
                                                        <?= $availableWidgets[$widget['widget_type']]['description'] ?>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <label class="flex items-center">
                                                    <input type="checkbox" class="widget-visibility mr-2" 
                                                           <?= $widget['is_visible'] ? 'checked' : '' ?>>
                                                    <span class="text-sm text-gray-600">Видимый</span>
                                                </label>
                                                <button onclick="removeWidget(this)" class="text-red-600 hover:text-red-800">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Предварительный просмотр -->
                <div class="mt-8">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">Предварительный просмотр</h2>
                        </div>
                        <div class="p-6">
                            <div id="preview-area" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <!-- Здесь будет отображаться предварительный просмотр -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form id="save-form" method="POST" class="hidden">
        <input type="hidden" name="widgets" id="widgets-data">
    </form>

    <script>
        // Инициализация Sortable для drag & drop
        const availableContainer = document.getElementById('available-widgets');
        const activeContainer = document.getElementById('active-widgets');

        const availableSortable = Sortable.create(availableContainer, {
            group: {
                name: 'widgets',
                pull: 'clone',
                put: false
            },
            sort: false,
            onAdd: function(evt) {
                evt.item.remove(); // Удаляем клон из available
            }
        });

        const activeSortable = Sortable.create(activeContainer, {
            group: {
                name: 'widgets',
                pull: true,
                put: true
            },
            animation: 150,
            onAdd: function(evt) {
                const item = evt.item;
                item.classList.add('active-widget', 'border-blue-200', 'bg-blue-50');
                item.classList.remove('border-gray-200', 'hover:bg-gray-50');
                
                // Добавляем контролы для активного виджета
                const controls = item.querySelector('.flex.items-center.justify-between') || 
                                item.querySelector('.flex.items-center');
                if (!controls.querySelector('.widget-visibility')) {
                    const controlsDiv = document.createElement('div');
                    controlsDiv.className = 'flex items-center space-x-2';
                    controlsDiv.innerHTML = `
                        <label class="flex items-center">
                            <input type="checkbox" class="widget-visibility mr-2" checked>
                            <span class="text-sm text-gray-600">Видимый</span>
                        </label>
                        <button onclick="removeWidget(this)" class="text-red-600 hover:text-red-800">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    controls.parentElement.classList.add('justify-between');
                    controls.parentElement.appendChild(controlsDiv);
                }
                
                updatePreview();
            },
            onUpdate: function(evt) {
                updatePreview();
            }
        });

        function removeWidget(button) {
            const widget = button.closest('.widget-item');
            widget.remove();
            updatePreview();
        }

        function saveWidgets() {
            const activeWidgets = document.querySelectorAll('#active-widgets .active-widget');
            const widgets = Array.from(activeWidgets).map((widget, index) => {
                const type = widget.dataset.type;
                const config = JSON.parse(widget.dataset.config || '{}');
                const visible = widget.querySelector('.widget-visibility').checked;
                
                return {
                    type: type,
                    config: config,
                    visible: visible,
                    position: index
                };
            });

            document.getElementById('widgets-data').value = JSON.stringify(widgets);
            document.getElementById('save-form').submit();
        }

        function resetToDefault() {
            if (confirm('Сбросить настройки дашборда к значениям по умолчанию?')) {
                const defaultWidgets = [
                    {type: 'orders_stats', config: {}, visible: true},
                    {type: 'recent_orders', config: {limit: 5}, visible: true},
                    {type: 'carriers_stats', config: {}, visible: true},
                    {type: 'quick_actions', config: {}, visible: true}
                ];
                
                document.getElementById('widgets-data').value = JSON.stringify(defaultWidgets);
                document.getElementById('save-form').submit();
            }
        }

        function updatePreview() {
            const previewArea = document.getElementById('preview-area');
            const activeWidgets = document.querySelectorAll('#active-widgets .active-widget');
            
            previewArea.innerHTML = '';
            
            activeWidgets.forEach(widget => {
                const type = widget.dataset.type;
                const visible = widget.querySelector('.widget-visibility').checked;
                
                if (visible) {
                    const previewWidget = document.createElement('div');
                    previewWidget.className = 'p-4 border border-gray-200 rounded-lg bg-gray-50';
                    previewWidget.innerHTML = `
                        <div class="text-sm font-medium text-gray-700">
                            ${widget.querySelector('h3').textContent}
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            Виджет: ${type}
                        </div>
                    `;
                    previewArea.appendChild(previewWidget);
                }
            });
        }

        // Инициализация предварительного просмотра
        updatePreview();

        // Обновление превью при изменении видимости
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('widget-visibility')) {
                updatePreview();
            }
        });
    </script>
</body>
</html>