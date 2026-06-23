<?php
/**
 * Добавление раздела — /moderator/sections/add
 */

// TODO: Проверка авторизации (шаг 11)

$pageTitle = 'Добавить раздел';
$breadcrumbs = breadcrumbs_generate([
    ['title' => 'Модерация', 'url' => '/moderator/list'],
    ['title' => 'Разделы', 'url' => '/moderator/sections'],
    ['title' => 'Добавить', 'url' => null],
]);

// TODO: Форма добавления раздела (шаг 14)

ob_start();
?>
<h2>Добавить раздел</h2>

<!-- TODO: Форма добавления раздела (шаг 14) -->

<div class="alert alert-info">
    Добавление раздела будет реализовано на шаге 14.
</div>

<p>
    <a href="/moderator/sections" class="btn btn-outline-secondary">← Назад к разделам</a>
</p>

<?php
$content = ob_get_clean();

require __DIR__ . '/../templates/layout.php';
