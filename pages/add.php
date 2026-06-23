<?php
/**
 * Страница «Добавить сайт» — /add
 */

$pageTitle = 'Добавить сайт';
$breadcrumbs = breadcrumbs_static('Добавить сайт');

// TODO: Загрузка разделов для селекта (шаг 10)

ob_start();
?>
<h2>Добавить сайт в каталог</h2>

<!-- TODO: Форма добавления сайта (шаг 10) -->

<div class="alert alert-info">
    Форма добавления сайта будет реализована на шаге 10.
</div>

<?php
$content = ob_get_clean();

require __DIR__ . '/../templates/layout.php';
