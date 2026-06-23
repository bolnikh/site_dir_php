<?php
/**
 * Модерация отдельного сайта — /moderator/moderate?id=N
 */

// TODO: Проверка авторизации (шаг 11)

$siteId = $_GET['id'] ?? null;

if (!$siteId) {
    http_response_code(404);
    echo '<h1>404 — Сайт не найден</h1>';
    return;
}

$pageTitle = 'Модерация сайта';
$breadcrumbs = breadcrumbs_generate([
    ['title' => 'Модерация', 'url' => '/moderator/list'],
    ['title' => 'Сайт #' . $siteId, 'url' => null],
]);

// TODO: Загрузка данных сайта и кнопки одобрить/отклонить (шаг 13)

ob_start();
?>
<h2>🛡️ Модерация сайта</h2>

<!-- TODO: Данные сайта, кнопки одобрить/отклонить, форма редактирования (шаг 13) -->

<div class="alert alert-info">
    Страница модерации будет реализована на шаге 13.
</div>

<p>
    <a href="/moderator/list" class="btn btn-outline-secondary">← Назад к списку</a>
</p>

<?php
$content = ob_get_clean();

require __DIR__ . '/../templates/layout.php';
