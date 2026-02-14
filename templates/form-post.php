<?php
// templates/form-post.php - Универсальная форма добавления/редактирования
// Ожидаемые переменные: 
// $pageTitle (string), $data (array), $categories (list), $tags (list), 
// $selectedCats (array ID), $selectedTags (array ID), $errors (array)

// Распаковка контактов для удобства, если они есть
$contactsArr = is_array($data['contacts'] ?? null) ? $data['contacts'] : json_decode($data['contacts'] ?? '{}', true);
?>

<div class="col-md-9 mx-auto">
    <div class="card shadow border-0 mb-5">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><?= h($pageTitle) ?></h4>
        </div>
        <div class="card-body p-4">
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?= h($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <h5 class="border-bottom pb-2 mb-3 text-primary">Основная информация</h5>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Название заведения <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" value="<?= h($data['title'] ?? '') ?>" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Категории <span class="text-danger">*</span></label>
                        <select name="categories[]" class="form-select" multiple size="6" required>
                            <?php foreach($categories as $cat): ?>
                                <?php $isSelected = in_array($cat['cat_id'], $selectedCats) ? 'selected' : ''; ?>
                                <option value="<?= $cat['cat_id'] ?>" <?= $isSelected ?>>
                                    <?= h($cat['cat_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Зажмите Ctrl (Cmd), чтобы выбрать несколько.</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Фото</label>
                        <?php if(!empty($data['photo'])): ?>
                            <div class="d-flex align-items-center mb-2">
                                <img src="<?= h($data['photo']) ?>" class="rounded me-2" style="width: 50px; height: 50px; object-fit: cover;">
                                <small class="text-muted">Текущее фото</small>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="photo" class="form-control">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Адрес</label>
                    <input type="text" name="address" class="form-control" value="<?= h($data['address'] ?? '') ?>" required>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Описание</label>
                    <textarea name="description" class="form-control" rows="6" required><?= h($data['description'] ?? '') ?></textarea>
                </div>

                <h5 class="border-bottom pb-2 mb-3 text-primary">Контакты</h5>
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Телефон</label>
                        <input type="text" name="phone" class="form-control" value="<?= h($contactsArr['phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">WhatsApp</label>
                        <input type="text" name="whatsapp" class="form-control" placeholder="770..." value="<?= h($contactsArr['whatsapp'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Instagram</label>
                        <input type="text" name="instagram" class="form-control" placeholder="@login" value="<?= h($contactsArr['instagram'] ?? '') ?>">
                    </div>
                </div>

                <h5 class="border-bottom pb-2 mb-3 text-primary">Удобства</h5>
                <div class="row mb-4">
                    <?php foreach($tags as $tag): ?>
                        <?php $isChecked = in_array($tag['attr_id'], $selectedTags) ? 'checked' : ''; ?>
                        <div class="col-md-4 col-6 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="tags[]" value="<?= $tag['attr_id'] ?>" id="tag_<?= $tag['attr_id'] ?>" <?= $isChecked ?>>
                                <label class="form-check-label" for="tag_<?= $tag['attr_id'] ?>">
                                    <i class="fa-solid <?= h($tag['attr_icon']) ?> text-muted me-1"></i> <?= h($tag['attr_name']) ?>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="dashboard.php" class="btn btn-secondary px-4">Назад</a>
                    <button type="submit" class="btn btn-success px-4 btn-lg">Сохранить</button>
                </div>

            </form>
        </div>
    </div>
</div>