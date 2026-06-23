<?php
/**
 * Страница отдельного сайта — /site/slug
 */

$slug = $_GET['slug'] ?? null;

if (!$slug) {
    http_response_code(404);
    render_page('404', breadcrumbs_static('Сайт не найден'), function () {
        echo '<h1>404 — Сайт не найден</h1>';
    });
    return;
}

// 1. Найти опубликованный сайт по slug (кэш 30 мин)
$site = $cache->remember('site:' . $slug, function () use ($db, $slug) {
    return $db->fetch(
        'SELECT st.*, s.name as section_name, s.slug as section_slug, s.path as section_path
         FROM sites st
         JOIN sections s ON st.section_id = s.id
         WHERE st.slug = ? AND st.status = 1',
        [$slug]
    );
}, 1800);

if (!$site) {
    http_response_code(404);
    render_page('404', breadcrumbs_static('Сайт не найден'), function () {
        echo '<h1>404 — Сайт не найден</h1>';
    });
    return;
}

// 2. Хлебные крошки: Начало > ... > раздел (ссылка) > сайт (без ссылки)
// breadcrumbs_from_path даёт цепочку до раздела (последний без ссылки)
// Меняем: разделу добавляем ссылку, сайт — последний без ссылки
$sectionBreadcrumbs = breadcrumbs_from_path($db->getConnection(), (int) $site['section_id']);

// Предпоследнему элементу (раздел сайта) ставим ссылку
$lastIdx = count($sectionBreadcrumbs) - 1;
if ($lastIdx >= 0) {
    $sectionBreadcrumbs[$lastIdx]['url'] = '/section/' . $site['section_slug'];
}

// Добавляем сайт как последний элемент (без ссылки)
$breadcrumbs = $sectionBreadcrumbs;
$breadcrumbs[] = [
    'label' => $site['name'],
    'url' => null,
];

render_page($site['name'], $breadcrumbs, function () use ($site) {
    ?>
    <div class="site-detail">
        <h2><?= h($site['name']) ?></h2>
        <hr>
        <div class="site-date">
            добавлен: <?= h(date('d.m.Y', strtotime($site['created_at']))) ?>
        </div>
        <?php if (!empty($site['description'])): ?>
            <div class="site-description mt-3">
                <?= clean_html($site['description']) ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
});
