<?php
/**
 * Страница отдельного сайта — /site/slug
 */

$slug = $_GET['slug'] ?? null;

if (!$slug) {
    http_response_code(404);
    echo '<h1>404 — Сайт не найден</h1>';
    return;
}

// TODO: Загрузка данных сайта (шаг 8)

render_page('Сайт', breadcrumbs_generate([
    ['label' => 'Сайт', 'url' => null],
]), function () {
    ?>
    <div class="site-detail">
        <h2>Сайт</h2>
        <!-- TODO: Дата добавления, описание -->
    </div>
    <?php
});
