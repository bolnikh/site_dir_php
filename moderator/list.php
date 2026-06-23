<?php
/**
 * Список сайтов на модерации — /moderator/list
 */

// TODO: Проверка авторизации (шаг 11)

$pageTitle = 'Сайты на модерации';
$breadcrumbs = breadcrumbs_generate([
    ['title' => 'Модерация', 'url' => null],
]);

// TODO: Загрузка списка сайтов на модерации (шаг 12)

ob_start();
?>
<h2>Сайты на модерации</h2>

<!-- TODO: Список сайтов на модерации (шаг 12) -->

<div class="alert alert-info">
    Список сайтов на модерации будет реализован на шаге 12.
</div>

<p>
    <a href="/moderator/sections" class="btn btn-outline-secondary">Управление разделами</a>
</p>

<?php
$content = ob_get_clean();

require __DIR__ . '/../templates/layout.php';
