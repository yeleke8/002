<?php
// auth.php
require 'config.php';

// Получаем метод запроса
$method = $_SERVER['REQUEST_METHOD'];

// Читаем JSON входные данные
$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? '';

if ($method === 'POST') {
    
    // --- РЕГИСТРАЦИЯ ---
    if ($action === 'register') {
        if (!isset($input['login'], $input['password'], $input['user_name'], $input['user_phone'])) {
            sendResponse(['error' => 'Заполните все поля'], 400);
        }

        // Проверка существования логина
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE login = ?");
        $stmt->execute([$input['login']]);
        if ($stmt->fetch()) {
            sendResponse(['error' => 'Пользователь с таким логином уже существует'], 409);
        }

        // Хеширование пароля
        $passwordHash = password_hash($input['password'], PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (login, password, user_name, user_phone, user_type) VALUES (?, ?, ?, ?, 'user')";
        $stmt = $pdo->prepare($sql);
        
        try {
            $stmt->execute([$input['login'], $passwordHash, $input['user_name'], $input['user_phone']]);
            sendResponse(['message' => 'Регистрация успешна', 'user_id' => $pdo->lastInsertId()], 201);
        } catch (Exception $e) {
            sendResponse(['error' => 'Ошибка при регистрации'], 500);
        }
    }

    // --- ВХОД (LOGIN) ---
    elseif ($action === 'login') {
        if (!isset($input['login'], $input['password'])) {
            sendResponse(['error' => 'Введите логин и пароль'], 400);
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->execute([$input['login']]);
        $user = $stmt->fetch();

        if ($user && password_verify($input['password'], $user['password'])) {
            // Удаляем пароль из ответа для безопасности
            unset($user['password']);
            
            // В реальном проекте здесь нужно генерировать JWT токен
            sendResponse([
                'message' => 'Успешный вход',
                'user' => $user
            ]);
        } else {
            sendResponse(['error' => 'Неверный логин или пароль'], 401);
        }
    } else {
        sendResponse(['error' => 'Неверное действие'], 400);
    }
} else {
    sendResponse(['error' => 'Метод не поддерживается'], 405);
}
?>