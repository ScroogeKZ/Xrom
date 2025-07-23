<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = \Database::getInstance()->getConnection();
        
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($phone) || empty($password)) {
            throw new Exception('Заполните все поля');
        }
        
        $stmt = $db->prepare("SELECT * FROM clients WHERE phone = ?");
        $stmt->execute([$phone]);
        $client = $stmt->fetch();
        
        if (!$client || !password_verify($password, $client['password_hash'])) {
            throw new Exception('Неверный телефон или пароль');
        }
        
        $_SESSION['client_id'] = $client['id'];
        $_SESSION['client_name'] = $client['name'];
        
        header('Location: /client/dashboard.php');
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в личный кабинет - Хром-KZ</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white p-8 border border-gray-200">
        <div class="text-center mb-8">
            <img src="/assets/logo.png" alt="Хром-KZ" class="h-8 w-8 mx-auto mb-4" onerror="this.style.display='none'">
            <h1 class="text-2xl font-medium text-gray-900">Личный кабинет</h1>
            <p class="text-gray-600 mt-2">Войдите для отслеживания заказов</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 mb-6">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Телефон</label>
                <input type="tel" name="phone" required 
                       class="w-full px-3 py-3 border border-gray-300 focus:outline-none focus:border-blue-500 text-lg"
                       placeholder="+7 777 123 45 67"
                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Пароль</label>
                <input type="password" name="password" required 
                       class="w-full px-3 py-3 border border-gray-300 focus:outline-none focus:border-blue-500 text-lg">
            </div>

            <button type="submit" 
                    class="w-full bg-blue-600 text-white py-3 px-4 text-lg hover:bg-blue-700 focus:outline-none">
                Войти
            </button>
        </form>

        <div class="mt-6 text-center space-y-3">
            <p class="text-gray-600">
                Нет аккаунта? 
                <a href="/client/register.php" class="text-blue-600 hover:text-blue-700">Зарегистрироваться</a>
            </p>
            <a href="/" class="text-gray-500 hover:text-gray-700 text-sm">← На главную</a>
        </div>
    </div>
</body>
</html>