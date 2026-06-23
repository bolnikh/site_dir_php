<?php
/**
 * Редактирование раздела — /moderator/sections/edit?id=N
 */

require_moderator();

$sectionId = $_GET['id'] ?? null;

if (!$sectionId) {
    http_response_code(404);
    echo '<h1>404 — Раздел не найден</h1>';
    return;
}

render_page('Редактировать раздел', breadcrumbs_generate([
    ['label' => 'Модерация', 'url' => '/moderator/list'],
    ['label' => 'Разделы', 'url' => '/moderator/sections'],
    ['label' => 'Редактировать', 'url' => null],
]), function () use ($sectionId) {
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
});
