<?php
// dashboard.php - Личный кабинет (Full Version)
require_once 'templates/header.php';

// 1. Проверка доступа
if (!is_logged_in()) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$msg = '';

// --- 2. ОБРАБОТКА ФОРМЫ НАСТРОЕК (Смена данных) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $new_pass = trim($_POST['new_password']);
    
    // Простая валидация
    if (mb_strlen($name) < 2) {
        $msg = "<div class='alert alert-danger'>Имя слишком короткое!</div>";
    } else {
        // Если пароль не меняют - оставляем старый
        if (!empty($new_pass)) {
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET user_name = ?, user_phone = ?, password = ? WHERE user_id = ?");
            $stmt->execute([$name, $phone, $hash, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET user_name = ?, user_phone = ? WHERE user_id = ?");
            $stmt->execute([$name, $phone, $user_id]);
        }
        
        // Обновляем сессию
        $_SESSION['user_name'] = $name;
        $msg = "<div class='alert alert-success'>Профиль успешно обновлен!</div>";
    }
}

// Получаем свежие данные пользователя
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmtUser->execute([$user_id]);
$currentUser = $stmtUser->fetch();

// --- 3. СТАТИСТИКА (Только для владельцев/админов) ---
$stats = [
    'views' => 0,
    'rating_avg' => 0,
    'posts_count' => 0
];

if ($user_type !== 'user') {
    // Получаем список постов
    if ($user_type === 'admin') {
        $stmtPosts = $pdo->query("SELECT * FROM post ORDER BY created_at DESC");
    } else {
        $stmtPosts = $pdo->prepare("SELECT * FROM post WHERE owner_id = ? ORDER BY created_at DESC");
        $stmtPosts->execute([$user_id]);
    }
    $my_posts = $stmtPosts->fetchAll();

    // Считаем статистику
    $stats['posts_count'] = count($my_posts);
    foreach ($my_posts as $p) {
        $stats['views'] += $p['views'];
    }
    // Средний рейтинг по всем заведениям
    if ($stats['posts_count'] > 0) {
        $sumRating = array_sum(array_column($my_posts, 'rating_avg'));
        $stats['rating_avg'] = $sumRating / $stats['posts_count'];
    }
} else {
    // Для обычных пользователей - получаем избранное
    $stmtFav = $pdo->prepare("SELECT p.* FROM post p JOIN s_favorites f ON p.post_id = f.post_id WHERE f.user_id = ?");
    $stmtFav->execute([$user_id]);
    $favs = $stmtFav->fetchAll();
}

// --- ВЕРСТКА ---
// Примечание: header.php открывает container и row. Мы используем свою колонку меню.
?>

<div class="col-md-3 mb-4">
    <div class="card shadow-sm border-0">
        <div class="card-body text-center">
            <div class="mb-3">
                <i class="fa-solid fa-circle-user fa-5x text-secondary"></i>
            </div>
            <h5 class="fw-bold"><?= h($currentUser['user_name']) ?></h5>
            <p class="text-muted small"><?= ($user_type === 'owner') ? 'Владелец бизнеса' : (($user_type === 'admin') ? 'Администратор' : 'Пользователь') ?></p>
        </div>
        <div class="list-group list-group-flush">
            <a href="#dashboard" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                <i class="fa-solid fa-gauge me-2"></i> Обзор
            </a>
            <a href="#settings" class="list-group-item list-group-item-action" data-bs-toggle="list">
                <i class="fa-solid fa-gear me-2"></i> Настройки профиля
            </a>
            <a href="login.php?logout=1" class="list-group-item list-group-item-action text-danger">
                <i class="fa-solid fa-right-from-bracket me-2"></i> Выход
            </a>
        </div>
    </div>
</div>

<div class="col-md-9">
    <?= $msg ?>
    
    <div class="tab-content">
        
        <div class="tab-pane fade show active" id="dashboard">
            
            <?php if ($user_type === 'admin' || $user_type === 'owner'): ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold mb-0">Мой бизнес</h3>
                    <a href="add.php" class="btn btn-success"><i class="fa-solid fa-plus"></i> Добавить заведение</a>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h6 class="text-uppercase opacity-75 small">Просмотры</h6>
                                <h2 class="fw-bold mb-0"><i class="fa-regular fa-eye"></i> <?= $stats['views'] ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-warning text-dark border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h6 class="text-uppercase opacity-75 small">Средний рейтинг</h6>
                                <h2 class="fw-bold mb-0"><i class="fa-solid fa-star"></i> <?= number_format($stats['rating_avg'], 1) ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-info text-white border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h6 class="text-uppercase opacity-75 small">Заведений</h6>
                                <h2 class="fw-bold mb-0"><i class="fa-solid fa-store"></i> <?= $stats['posts_count'] ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-body p-0">
                        <?php if(!empty($my_posts)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4">Заведение</th>
                                            <th>Статус</th>
                                            <th>Рейтинг</th>
                                            <th class="text-end pe-4">Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($my_posts as $p): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?= h($p['photo']) ?>" class="rounded me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                                        <div>
                                                            <a href="post.php?slug=<?= $p['slug'] ?>" class="fw-bold text-dark text-decoration-none"><?= h($p['title']) ?></a>
                                                            <div class="small text-muted"><?= date('d.m.Y', strtotime($p['created_at'])) ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if($p['status'] == 1): ?>
                                                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">Опубликовано</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 rounded-pill">На проверке</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-warning"><i class="fa-solid fa-star"></i> <?= number_format($p['rating_avg'], 1) ?></span>
                                                    <small class="text-muted">(<?= $p['rating_count'] ?>)</small>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <a href="edit.php?id=<?= $p['post_id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="p-5 text-center text-muted">
                                <i class="fa-solid fa-folder-open fa-3x mb-3"></i>
                                <p>У вас пока нет добавленных заведений.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php else: ?>
                <h3 class="fw-bold mb-4">Избранное ❤️</h3>
                
                <?php if(!empty($favs)): ?>
                    <div class="row">
                        <?php 
                        foreach ($favs as $post) {
                            include 'templates/card.php';
                        } 
                        ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info py-4 text-center">
                        <i class="fa-regular fa-heart fa-2x mb-3 d-block"></i>
                        Вы пока ничего не добавили в избранное. <a href="index.php">Перейти к поиску</a>
                    </div>
                <?php endif; ?>

            <?php endif; ?>

        </div> <div class="tab-pane fade" id="settings">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-4">Редактирование профиля</h4>
                    
                    <form method="POST">
                        <input type="hidden" name="update_profile" value="1">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Ваше имя</label>
                                <input type="text" name="name" class="form-control" value="<?= h($currentUser['user_name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Телефон</label>
                                <input type="text" name="phone" class="form-control" value="<?= h($currentUser['user_phone']) ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Логин (нельзя изменить)</label>
                            <input type="text" class="form-control bg-light" value="<?= h($currentUser['login']) ?>" readonly>
                        </div>

                        <hr class="my-4">
                        <h5 class="mb-3">Безопасность</h5>

                        <div class="mb-4">
                            <label class="form-label">Новый пароль</label>
                            <input type="password" name="new_password" class="form-control" placeholder="Оставьте пустым, если не хотите менять">
                            <div class="form-text">Минимум 6 символов</div>
                        </div>

                        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                    </form>
                </div>
            </div>
        </div> </div>
</div>

<?php require_once 'templates/footer.php'; ?>