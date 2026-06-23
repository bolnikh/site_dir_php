<?php
/**
 * Управление разделами — /moderator/sections
 */

// TODO: Проверка авторизации (шаг 11)

$pageTitle = 'Управление разделами';
$breadcrumbs = breadcrumbs_generate([
    ['title' => 'Модерация', 'url' => '/moderator/list'],
    ['title' => 'Разделы', 'url' => null],
]);

// TODO: Дерево разделов, фильтр, кнопки (шаг 14)

ob_start();
?>
<h2>Управление разделами</h2>

<!-- TODO: Фильтр по имени, дерево разделов, кнопки добавить/редактировать/удалить (шаг 14) -->

<div class="alert alert-info">
    Управление разделами будет реализовано на шаге 14.
</div>

<p>
    <a href="/moderator/list" class="btn btn-outline-secondary">← Назад к списку</a>
</p>

<?php
$content = ob_get_clean();

require __DIR__ . '/../templates/layout.php';
