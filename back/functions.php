<?php
// back/functions.php - Основные функции для работы с БД

// Получить главные категории (без родителей)
function getParentCategories($pdo) {
    $stmt = $pdo->query("SELECT * FROM categories WHERE cat_parent_id IS NULL");
    return $stmt->fetchAll();
}

// Получить последние добавленные заведения
function getLatestPosts($pdo, $limit = 6) {
    $stmt = $pdo->query("SELECT * FROM post WHERE status = 1 ORDER BY created_at DESC LIMIT $limit");
    return $stmt->fetchAll();
}


// Получить заведение по slug
function getPostBySlug($pdo, $slug) {
    $stmt = $pdo->prepare("SELECT * FROM post WHERE slug = ? AND status = 1");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

// Проверка авторизации пользователя
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Безопасный вывод данных
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>