<?php
// post.php - Детальная страница заведения (Full Version)
require_once 'templates/header.php';
require_once 'templates/sidebar.php';

echo '<div class="col-md-9">';

$slug = $_GET['slug'] ?? '';
$post = getPostBySlug($pdo, $slug);

if (!$post) {
    echo "<div class='alert alert-danger'>Заведение не найдено или удалено.</div>";
    echo '</div>'; // Закрываем col-md-9
    require_once 'templates/footer.php';
    exit;
}

// 1. Увеличиваем просмотры (защита от накрутки F5 через сессию)
if (!isset($_SESSION['viewed_posts'][$post['post_id']])) {
    $pdo->prepare("UPDATE post SET views = views + 1 WHERE post_id = ?")->execute([$post['post_id']]);
    $_SESSION['viewed_posts'][$post['post_id']] = true;
    $post['views']++; // Обновляем визуально для текущего просмотра
}

// 2. Получаем дополнительные фото (Галерея)
$stmtPhotos = $pdo->prepare("SELECT * FROM photos WHERE post_id = ? ORDER BY sort_order ASC");
$stmtPhotos->execute([$post['post_id']]);
$photos = $stmtPhotos->fetchAll();

// 3. Получаем теги/удобства (Wi-Fi, Парковка и т.д.)
$stmtTags = $pdo->prepare("
    SELECT t.* FROM tags t 
    JOIN s_tags st ON t.attr_id = st.attr_id 
    WHERE st.post_id = ?
");
$stmtTags->execute([$post['post_id']]);
$tags = $stmtTags->fetchAll();

// 4. Парсим JSON данные
$contacts = json_decode($post['contacts'], true) ?? [];
$attributes = json_decode($post['attributes'], true) ?? [];
$worktime = json_decode($post['worktime'], true) ?? [];

// 5. Получаем отзывы
$stmtComments = $pdo->prepare("
    SELECT c.*, u.user_name 
    FROM comments c 
    JOIN users u ON c.user_id = u.user_id 
    WHERE c.post_id = ? AND c.is_approved = 1 
    ORDER BY c.created_at DESC
");
$stmtComments->execute([$post['post_id']]);
$comments = $stmtComments->fetchAll();
?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="index.php">Главная</a></li>
    <li class="breadcrumb-item active" aria-current="page"><?= h($post['title']) ?></li>
  </ol>
</nav>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h1 class="fw-bold mb-1"><?= h($post['title']) ?></h1>
        <p class="text-muted mb-0"><i class="fa-solid fa-location-dot text-danger"></i> <?= h($post['address']) ?></p>
    </div>
    <div class="text-end">
        <span class="badge bg-success fs-5">
            <?= number_format($post['rating_avg'], 1) ?> <i class="fa-solid fa-star text-warning"></i>
        </span>
        <div class="small text-muted mt-1"><?= $post['rating_count'] ?> отзывов</div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        
        <div id="carouselGallery" class="carousel slide mb-4 rounded overflow-hidden shadow-sm" data-bs-ride="carousel">
            <div class="carousel-inner" style="max-height: 400px;">
                <div class="carousel-item active">
                    <img src="<?= h($post['photo']) ?>" class="d-block w-100" style="object-fit: cover; height: 400px;" alt="Main">
                </div>
                <?php foreach($photos as $photo): ?>
                <div class="carousel-item">
                    <img src="<?= h($photo['photo_url']) ?>" class="d-block w-100" style="object-fit: cover; height: 400px;" alt="Photo">
                </div>
                <?php endforeach; ?>
            </div>
            <?php if(count($photos) > 0): ?>
                <button class="carousel-control-prev" type="button" data-bs-target="#carouselGallery" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon bg-dark rounded-circle p-3" aria-hidden="true"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#carouselGallery" data-bs-slide="next">
                    <span class="carousel-control-next-icon bg-dark rounded-circle p-3" aria-hidden="true"></span>
                </button>
            <?php endif; ?>
        </div>

        <?php if($tags): ?>
        <div class="mb-4">
            <h5 class="fw-bold">Удобства и услуги</h5>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach($tags as $tag): ?>
                    <span class="badge bg-light text-dark border p-2">
                        <i class="fa-solid <?= h($tag['attr_icon']) ?> text-primary me-1"></i> <?= h($tag['attr_name']) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h5 class="fw-bold">О заведении</h5>
                <p class="card-text text-secondary" style="line-height: 1.7;">
                    <?= nl2br(h($post['description'])) ?>
                </p>
                
                <?php if(!empty($attributes)): ?>
                    <hr>
                    <div class="row">
                        <?php if(isset($attributes['avg_check'])): ?>
                            <div class="col-md-6 mb-2">
                                <strong><i class="fa-solid fa-wallet text-muted"></i> Средний чек:</strong> 
                                <?= h($attributes['avg_check']) ?> ₸
                            </div>
                        <?php endif; ?>
                        <?php if(isset($attributes['cuisine'])): ?>
                            <div class="col-md-6 mb-2">
                                <strong><i class="fa-solid fa-utensils text-muted"></i> Кухня:</strong> 
                                <?= h($attributes['cuisine']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <h4 class="mb-3">Отзывы <span class="text-muted fs-6">(<?= count($comments) ?>)</span></h4>
        
        <?php if(is_logged_in()): ?>
            <div class="card mb-4 bg-light border-0">
                <div class="card-body">
                    <h6 class="fw-bold">Оставить отзыв</h6>
                    <form action="add_comment.php" method="POST">
                        <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                        <div class="mb-2">
                            <div class="rating-input d-inline-block">
                                <select name="rating" class="form-select form-select-sm w-auto" required>
                                    <option value="5">⭐⭐⭐⭐⭐ (5)</option>
                                    <option value="4">⭐⭐⭐⭐ (4)</option>
                                    <option value="3">⭐⭐⭐ (3)</option>
                                    <option value="2">⭐⭐ (2)</option>
                                    <option value="1">⭐ (1)</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-2">
                            <textarea name="comment" class="form-control" rows="3" placeholder="Расскажите о своих впечатлениях..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Отправить отзыв</button>
                    </form>
                </div>
            </div>
        <?php elseif(!is_logged_in()): ?>
            <div class="alert alert-info">
                <i class="fa-solid fa-lock"></i> <a href="login.php" class="alert-link">Войдите</a>, чтобы написать отзыв.
            </div>
        <?php endif; ?>

        <?php if(empty($comments)): ?>
            <p class="text-muted fst-italic">Отзывов пока нет. Будьте первыми!</p>
        <?php else: ?>
            <div class="list-group list-group-flush shadow-sm rounded">
                <?php foreach($comments as $comment): ?>
                    <div class="list-group-item p-3">
                        <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                            <div>
                                <h6 class="mb-0 fw-bold"><i class="fa-solid fa-user-circle text-secondary"></i> <?= h($comment['user_name']) ?></h6>
                                <div class="text-warning small">
                                    <?= str_repeat('<i class="fa-solid fa-star"></i>', $comment['rating']) ?>
                                    <?= str_repeat('<i class="fa-regular fa-star"></i>', 5 - $comment['rating']) ?>
                                </div>
                            </div>
                            <small class="text-muted"><?= date('d.m.Y', strtotime($comment['created_at'])) ?></small>
                        </div>
                        <p class="mb-2 mt-2"><?= nl2br(h($comment['comment'])) ?></p>
                        
                        <?php if($comment['owner_reply']): ?>
                            <div class="bg-light p-3 mt-2 rounded border-start border-3 border-success">
                                <strong class="text-success small"><i class="fa-solid fa-reply"></i> Ответ заведения:</strong>
                                <p class="mb-0 mt-1 small text-secondary"><?= nl2br(h($comment['owner_reply'])) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 sticky-top" style="top: 20px; z-index: 1;">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Контакты</h5>
                
                <ul class="list-unstyled mb-4">
                    <?php if(!empty($contacts['phone']) && $contacts['phone'] !== '-'): ?>
                    <li class="mb-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 p-2 rounded me-3 text-primary">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block">Телефон</small>
                                <a href="tel:<?= h($contacts['phone']) ?>" class="text-dark text-decoration-none fw-bold">
                                    <?= h($contacts['phone']) ?>
                                </a>
                            </div>
                        </div>
                    </li>
                    <?php endif; ?>

                    <?php if(!empty($contacts['whatsapp']) && $contacts['whatsapp'] !== '-'): ?>
                    <li class="mb-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-success bg-opacity-10 p-2 rounded me-3 text-success">
                                <i class="fa-brands fa-whatsapp"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block">WhatsApp</small>
                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $contacts['whatsapp']) ?>" target="_blank" class="text-dark text-decoration-none fw-bold">
                                    Написать
                                </a>
                            </div>
                        </div>
                    </li>
                    <?php endif; ?>

                    <?php if(!empty($contacts['instagram']) && $contacts['instagram'] !== '-'): ?>
                    <li class="mb-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-danger bg-opacity-10 p-2 rounded me-3 text-danger">
                                <i class="fa-brands fa-instagram"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block">Instagram</small>
                                <a href="https://instagram.com/<?= str_replace('@', '', $contacts['instagram']) ?>" target="_blank" class="text-dark text-decoration-none fw-bold">
                                    <?= h($contacts['instagram']) ?>
                                </a>
                            </div>
                        </div>
                    </li>
                    <?php endif; ?>
                </ul>

                <hr>

                <div class="d-grid gap-2">
                    <?php if(is_logged_in() && $_SESSION['user_type'] === 'user'): ?>
                        <button class="btn btn-outline-danger"><i class="fa-regular fa-heart"></i> В избранное</button>
                    <?php endif; ?>
                    
                    <?php if(is_logged_in() && ($_SESSION['user_type'] === 'admin' || ($_SESSION['user_type'] === 'owner' && $post['owner_id'] == $_SESSION['user_id']))): ?>
                        <a href="edit.php?id=<?= $post['post_id'] ?>" class="btn btn-secondary"><i class="fa-solid fa-pen-to-square"></i> Редактировать</a>
                    <?php endif; ?>
                </div>

                <?php if(isset($worktime) && !empty($worktime)): ?>
                    <div class="mt-4 p-3 bg-light rounded">
                        <small class="text-muted fw-bold text-uppercase">Режим работы</small>
                        <p class="mb-0 mt-1">Информация уточняется</p> 
                    </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</div>

<?php 
echo '</div>'; // Закрываем col-md-9
require_once 'templates/footer.php'; 
?>