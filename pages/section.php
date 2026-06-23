<?php
/**
 * Страница раздела / подраздела — /section/slug
 */

$pageTitle = 'Раздел';
$slug = $_GET['slug'] ?? null;

if (!$slug) {
    http_response_code(404);
    echo '<h1>404 — Раздел не найден</h1>';
    return;
}

// TODO: Загрузка раздела, подразделов, списка сайтов (шаг 7)

$breadcrumbs = breadcrumbs_generate([
    ['title' => 'Раздел', 'url' => null],
]);

ob_start();
?>
<h2><?= h($pageTitle) ?></h2>

<!-- TODO: Описание раздела -->
<!-- TODO: Подразделы -->
<!-- TODO: Список сайтов с пагинацией -->

<?php
$content = ob_get_clean();

require __DIR__ . '/../templates/layout.php';
