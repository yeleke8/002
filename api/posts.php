<?php
// posts.php
require 'config.php';

$id = $_GET['id'] ?? null;
$cat_id = $_GET['cat_id'] ?? null;
$search = $_GET['search'] ?? null;

// --- ПОЛУЧЕНИЕ ОДНОГО ПОСТА (ДЕТАЛЬНО) ---
if ($id) {
    // 1. Основная инфо
    $stmt = $pdo->prepare("SELECT * FROM post WHERE post_id = ?");
    $stmt->execute([$id]);
    $post = $stmt->fetch();

    if (!$post) {
        sendResponse(['error' => 'Post not found'], 404);
    }

    // Декодируем JSON поля из базы, чтобы Android получил их как объекты, а не строки
    $post['worktime'] = json_decode($post['worktime']);
    $post['attributes'] = json_decode($post['attributes']);
    $post['contacts'] = json_decode($post['contacts']);

    // 2. Фотографии
    $stmtPhotos = $pdo->prepare("SELECT photo_url FROM photos WHERE post_id = ? ORDER BY sort_order ASC");
    $stmtPhotos->execute([$id]);
    $post['gallery'] = $stmtPhotos->fetchAll(PDO::FETCH_COLUMN);

    // 3. Теги (удобства)
    $stmtTags = $pdo->prepare("
        SELECT t.attr_name, t.attr_icon 
        FROM s_tags st 
        JOIN tags t ON st.attr_id = t.attr_id 
        WHERE st.post_id = ?
    ");
    $stmtTags->execute([$id]);
    $post['tags'] = $stmtTags->fetchAll();

    // 4. Отзывы (только одобренные)
    $stmtComments = $pdo->prepare("
        SELECT c.rating, c.comment, c.created_at, u.user_name, c.owner_reply 
        FROM comments c 
        JOIN users u ON c.user_id = u.user_id 
        WHERE c.post_id = ? AND c.is_approved = 1 
        ORDER BY c.created_at DESC
    ");
    $stmtComments->execute([$id]);
    $post['reviews'] = $stmtComments->fetchAll();

    // Увеличиваем счетчик просмотров
    $pdo->prepare("UPDATE post SET views = views + 1 WHERE post_id = ?")->execute([$id]);

    sendResponse($post);
}

// --- ПОЛУЧЕНИЕ СПИСКА ПОСТОВ ---
else {
    $sql = "SELECT DISTINCT p.post_id, p.title, p.photo, p.rating_avg, p.rating_count, p.address, p.slug 
            FROM post p";
    $params = [];

    // Фильтр по категории
    if ($cat_id) {
        $sql .= " JOIN s_categories sc ON p.post_id = sc.post_id WHERE sc.cat_id = ?";
        $params[] = $cat_id;
    } 
    // Поиск
    elseif ($search) {
        $sql .= " WHERE (p.title LIKE ? OR p.description LIKE ? OR p.psevdonim LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $sql .= " AND p.status = 1 ORDER BY p.rating_avg DESC, p.views DESC LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $posts = $stmt->fetchAll();

    sendResponse(['posts' => $posts]);
}
?>