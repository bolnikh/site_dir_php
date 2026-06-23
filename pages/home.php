<?php
/**
 * Главная страница — /
 */

$pageTitle = 'Главная';
$breadcrumbs = []; // На главной хлебных крошек нет

// TODO: Загрузка разделов и последних сайтов (шаг 6)

ob_start();
?>
<div class="row">
    <div class="col-12 mb-4">
        <a href="/add" class="btn btn-primary">Добавить сайт</a>
    </div>
</div>

<!-- TODO: Сетка разделов -->

<!-- TODO: 10 последних сайтов -->

<?php
$content = ob_get_clean();

require __DIR__ . '/../templates/layout.php';
