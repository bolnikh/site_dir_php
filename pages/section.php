<?php
/**
 * Страница раздела / подраздела — /section/slug
 */

$slug = $_GET['slug'] ?? null;

if (!$slug) {
    http_response_code(404);
    echo '<h1>404 — Раздел не найден</h1>';
    return;
}

// TODO: Загрузка раздела, подразделов, списка сайтов (шаг 7)

render_page('Раздел', breadcrumbs_generate([
    ['label' => 'Раздел', 'url' => null],
]), function () {
    ?>
    <h2>Раздел</h2>

    <!-- TODO: Описание раздела -->
    <!-- TODO: Подразделы -->
    <!-- TODO: Список сайтов с пагинацией -->
    <?php
});
