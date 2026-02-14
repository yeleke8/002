<?php
// index.php - Главная страница
require_once 'templates/header.php';
// require_once 'templates/sidebar.php'; // Убираем отсюда, вставим ниже в верстку

// 1. Логика получения данных
// Получаем популярные (по рейтингу и кол-ву отзывов)
$stmtPopular = $pdo->query("SELECT * FROM post WHERE status = 1 ORDER BY rating_avg DESC, rating_count DESC LIMIT 3");
$popularPosts = $stmtPopular->fetchAll();

// Получаем свежие
$newPosts = getLatestPosts($pdo, 6);

// --- ХАК ДЛЯ ВЕРСТКИ ---
// Закрываем контейнер и строку хедера, чтобы вставить баннер во всю ширину
?>
    </div> </div> 

<div class="bg-primary text-white py-5 mb-5" style="background: linear-gradient(135deg, #0d6efd, #0dcaf0);">
    <div class="container py-5 text-center">
        <h1 class="display-4 fw-bold mb-3">Найди лучшее в Туркестане</h1>
        <p class="lead mb-4">Кафе, магазины, услуги, развлечения — всё в одном месте.</p>
        
        <div class="row justify-content-center">
            <div class="col-md-8">
                <form action="search.php" method="GET" class="d-flex bg-white p-2 rounded shadow-lg">
                    <input type="text" name="q" class="form-control border-0 form-control-lg" placeholder="Что вы ищете? (например: бургер, аптека...)" required>
                    <button type="submit" class="btn btn-warning btn-lg px-4 fw-bold">Найти</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="row">

        <?php require_once 'templates/sidebar.php'; ?>

        <div class="col-md-9">
            
            <?php if(!empty($popularPosts)): ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold mb-0"><i class="fa-solid fa-fire text-danger"></i> Популярные места</h3>
                </div>
                
                <div class="row">
                    <?php 
                    foreach ($popularPosts as $post) {
                        include 'templates/card.php'; 
                    } 
                    ?>
                </div>
                <hr class="my-5">
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0">🆕 Новые заведения</h3>
                <a href="search.php?q=" class="text-decoration-none">Смотреть все <i class="fa-solid fa-arrow-right"></i></a>
            </div>

            <div class="row">
                <?php 
                if (empty($newPosts)) {
                    echo '<div class="col-12"><p class="text-muted">Заведения пока не добавлены.</p></div>';
                } else {
                    foreach ($newPosts as $post) {
                        include 'templates/card.php';
                    }
                }
                ?>
            </div>

        </div> </div> <div class="row mt-5">
        <div class="col-12 mb-4">
            <div class="bg-light p-5 rounded-3 border text-center shadow-sm">
                <h2>Вы владелец бизнеса?</h2>
                <p class="text-muted">Добавьте своё заведение в каталог совершенно бесплатно и привлекайте новых клиентов.</p>
                <?php if(is_logged_in()): ?>
                    <a href="add.php" class="btn btn-success btn-lg"><i class="fa-solid fa-plus"></i> Добавить заведение</a>
                <?php else: ?>
                    <a href="register.php" class="btn btn-primary btn-lg">Зарегистрироваться</a>
                    <a href="login.php" class="btn btn-outline-secondary btn-lg ms-2">Войти</a>
                <?php endif; ?>
            </div>
        </div>
        <?php
require_once 'templates/footer.php'; 
?>