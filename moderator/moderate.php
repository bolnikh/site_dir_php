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

render_page('Модерация сайта', breadcrumbs_generate([
    ['label' => 'Модерация', 'url' => '/moderator/list'],
    ['label' => 'Сайт #' . $siteId, 'url' => null],
]), function () use ($siteId) {
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
});
