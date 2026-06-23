<?php
/**
 * Редактирование раздела — /moderator/sections/edit?id=N
 */

// TODO: Проверка авторизации (шаг 11)

$sectionId = $_GET['id'] ?? null;

if (!$sectionId) {
    http_response_code(404);
    echo '<h1>404 — Раздел не найден</h1>';
    return;
}

$pageTitle = 'Редактировать раздел';
$breadcrumbs = breadcrumbs_generate([
    ['title' => 'Модерация', 'url' => '/moderator/list'],
    ['title' => 'Разделы', 'url' => '/moderator/sections'],
    ['title' => 'Редактировать', 'url' => null],
]);

// TODO: Загрузка данных раздела и форма редактирования (шаг 14)

ob_start();
?>
<h2>Редактировать раздел</h2>

<!-- TODO: Форма редактирования раздела (шаг 14) -->

<div class="alert alert-info">
    Редактирование раздела будет реализовано на шаге 14.
</div>

<p>
    <a href="/moderator/sections" class="btn btn-outline-secondary">← Назад к разделам</a>
</p>

<?php
$content = ob_get_clean();

require __DIR__ . '/../templates/layout.php';
