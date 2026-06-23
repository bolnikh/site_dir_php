<?php
/**
 * Страница отдельного сайта — /site/slug
 */

$pageTitle = 'Сайт';
$slug = $_GET['slug'] ?? null;

if (!$slug) {
    http_response_code(404);
    echo '<h1>404 — Сайт не найден</h1>';
    return;
}

// TODO: Загрузка данных сайта (шаг 8)

$breadcrumbs = breadcrumbs_generate([
    ['title' => 'Сайт', 'url' => null],
]);

ob_start();
?>
<div class="site-detail">
    <h2><?= h($pageTitle) ?></h2>
    <!-- TODO: Дата добавления, описание -->
</div>
<?php
$content = ob_get_clean();

require __DIR__ . '/../templates/layout.php';
