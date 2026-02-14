<?php
// categories.php
require 'config.php';

// Получаем все категории
$stmt = $pdo->query("SELECT cat_id, cat_name, cat_slug, cat_parent_id FROM categories ORDER BY cat_parent_id ASC, cat_name ASC");
$allCategories = $stmt->fetchAll();

// Функция для построения дерева категорий
function buildTree(array $elements, $parentId = null) {
    $branch = array();

    foreach ($elements as $element) {
        if ($element['cat_parent_id'] == $parentId) {
            $children = buildTree($elements, $element['cat_id']);
            if ($children) {
                $element['subcategories'] = $children;
            }
            $branch[] = $element;
        }
    }

    return $branch;
}

$tree = buildTree($allCategories);

sendResponse(['categories' => $tree]);
?>