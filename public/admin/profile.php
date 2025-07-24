<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\CRM\CRMAuth;
use App\CRM\RoleManager;

// Проверка авторизации
CRMAuth::requireCRMAuth();

$currentUser = CRMAuth::getCurrentUser();
$roleManager = new RoleManager();

// Обработка обновления профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $position = $_POST['position'] ?? '';
    $department = $_POST['department'] ?? '';
    
    try {
        $stmt = $roleManager->db->prepare("
            UPDATE users 
            SET first_name = ?, last_name = ?, email = ?, phone = ?, position = ?, department = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$firstName, $lastName, $email, $phone, $position, $department, $currentUser['id']]);
        
        $success = "Профиль успешно обновлен";
        
        // Обновляем данные пользователя
        $currentUser['first_name'] = $firstName;
        $currentUser['last_name'] = $lastName;
        $currentUser['email'] = $email;
        $currentUser['phone'] = $phone;
        $currentUser['position'] = $position;
        $currentUser['department'] = $department;
        
    } catch (Exception $e) {
        $error = "Ошибка при обновлении профиля: " . $e->getMessage();
    }
}

// Смена пароля
if (isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if ($newPassword !== $confirmPassword) {
        $passwordError = "Новые пароли не совпадают";
    } elseif (strlen($newPassword) < 6) {
        $passwordError = "Пароль должен быть не менее 6 символов";
    } elseif (!password_verify($currentPassword, $currentUser['password_hash'])) {
        $passwordError = "Текущий пароль неверен";  
    } else {
        try {
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $roleManager->db->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newPasswordHash, $currentUser['id']]);
            
            $passwordSuccess = "Пароль успешно изменен";
        } catch (Exception $e) {
            $passwordError = "Ошибка при смене пароля: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль пользователя - CRM Хром-KZ</title>
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
                        <h1 class="text-2xl font-bold text-gray-900">Профиль пользователя</h1>
                        <p class="text-gray-600">Управление личными данными и настройками аккаунта</p>
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
                        <!-- Аватар и основная информация -->
                        <div class="lg:col-span-1">
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                                <div class="text-center">
                                    <div class="w-24 h-24 bg-blue-600 rounded-full mx-auto mb-4 flex items-center justify-center">
                                        <span class="text-white text-2xl font-bold">
                                            <?= strtoupper(substr($currentUser['first_name'] ?? $currentUser['username'], 0, 1)) ?>
                                        </span>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        <?= htmlspecialchars(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? '')) ?>
                                    </h3>
                                    <p class="text-gray-600">@<?= htmlspecialchars($currentUser['username']) ?></p>
                                    
                                    <!-- Роли пользователя -->
                                    <div class="mt-4 flex flex-wrap justify-center gap-2">
                                        <?php 
                                        $roles = $currentUser['roles'] ?? [];
                                        if (is_string($roles)) {
                                            $roles = trim($roles, '{}');
                                            $roles = $roles ? explode(',', $roles) : [];
                                        }
                                        foreach ($roles as $role): ?>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                            <?= htmlspecialchars(ucfirst($role)) ?>
                                        </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <!-- Статистика -->
                                <div class="mt-6 pt-6 border-t border-gray-200">
                                    <div class="space-y-3">
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Дата регистрации:</span>
                                            <span class="font-medium"><?= date('d.m.Y', strtotime($currentUser['created_at'])) ?></span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Последний вход:</span>
                                            <span class="font-medium">
                                                <?= $currentUser['last_login'] ? date('d.m.Y H:i', strtotime($currentUser['last_login'])) : 'Сейчас' ?>
                                            </span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Статус:</span>
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                Активен
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Формы редактирования -->
                        <div class="lg:col-span-2 space-y-6">
                            <!-- Основная информация -->
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                                <div class="p-6 border-b border-gray-200">
                                    <h2 class="text-lg font-semibold text-gray-900">Основная информация</h2>
                                </div>
                                
                                <form method="POST" class="p-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Имя</label>
                                            <input type="text" name="first_name" value="<?= htmlspecialchars($currentUser['first_name'] ?? '') ?>" 
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Фамилия</label>
                                            <input type="text" name="last_name" value="<?= htmlspecialchars($currentUser['last_name'] ?? '') ?>" 
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                            <input type="email" name="email" value="<?= htmlspecialchars($currentUser['email'] ?? '') ?>" 
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Телефон</label>
                                            <input type="tel" name="phone" value="<?= htmlspecialchars($currentUser['phone'] ?? '') ?>" 
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Должность</label>
                                            <input type="text" name="position" value="<?= htmlspecialchars($currentUser['position'] ?? '') ?>" 
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Отдел</label>
                                            <input type="text" name="department" value="<?= htmlspecialchars($currentUser['department'] ?? '') ?>" 
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        </div>
                                    </div>
                                    
                                    <div class="mt-6 flex justify-end">
                                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                            <i class="fas fa-save mr-2"></i>
                                            Сохранить изменения
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Смена пароля -->
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                                <div class="p-6 border-b border-gray-200">
                                    <h2 class="text-lg font-semibold text-gray-900">Смена пароля</h2>
                                </div>
                                
                                <?php if (isset($passwordSuccess)): ?>
                                <div class="mx-6 mt-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    <?= htmlspecialchars($passwordSuccess) ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (isset($passwordError)): ?>
                                <div class="mx-6 mt-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                                    <i class="fas fa-exclamation-circle mr-2"></i>
                                    <?= htmlspecialchars($passwordError) ?>
                                </div>
                                <?php endif; ?>
                                
                                <form method="POST" class="p-6">
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Текущий пароль</label>
                                            <input type="password" name="current_password" required
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Новый пароль</label>
                                            <input type="password" name="new_password" required minlength="6"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Подтвердите новый пароль</label>
                                            <input type="password" name="confirm_password" required minlength="6"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        </div>
                                    </div>
                                    
                                    <div class="mt-6 flex justify-end">
                                        <button type="submit" name="change_password" value="1" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                                            <i class="fas fa-key mr-2"></i>
                                            Изменить пароль
                                        </button>
                                    </div>
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