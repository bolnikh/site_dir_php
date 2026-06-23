<?php
/**
 * Страница раздела / подраздела — /section/slug
 */

$slug = $_GET['slug'] ?? null;

if (!$slug) {
    http_response_code(404);
    render_page('404', breadcrumbs_static('Раздел не найден'), function () {
        echo '<h1>404 — Раздел не найден</h1>';
    });
    return;
}

// 1. Найти раздел по slug
$section = $db->fetch('SELECT * FROM sections WHERE slug = ?', [$slug]);

if (!$section) {
    http_response_code(404);
    render_page('404', breadcrumbs_static('Раздел не найден'), function () {
        echo '<h1>404 — Раздел не найден</h1>';
    });
    return;
}

// 2. Хлебные крошки через path
$breadcrumbs = breadcrumbs_from_path($db->getConnection(), (int) $section['id']);

// 3. Подразделы (кэш 1 час)
$children = $cache->remember('sections:children:' . $section['id'], function () use ($db, $section) {
    return $db->fetchAll(
        'SELECT * FROM sections WHERE parent_id = ? ORDER BY name',
        [(int) $section['id']]
    );
}, 3600);

// 4. Количество сайтов (включая дочерние разделы)
$siteCount = $cache->remember('sites:count:section:' . $section['id'], function () use ($db, $section) {
    return (int) $db->fetchColumn(
        "SELECT COUNT(*) FROM sites
         WHERE section_id IN (
             SELECT id FROM sections WHERE path LIKE ?
         ) AND status = 1",
        [$section['path'] . '%']
    );
}, 300);

// 5. Пагинация
$perPage = $appConfig['sites_per_page'] ?? 20;
$currentPage = max(1, (int) ($_GET['pg'] ?? 1));
$totalPages = max(1, (int) ceil($siteCount / $perPage));
$currentPage = min($currentPage, $totalPages);

$sites = $cache->remember('sites:section:' . $section['id'] . ':page:' . $currentPage, function () use ($db, $section, $perPage, $currentPage) {
    $offset = ($currentPage - 1) * $perPage;
    return $db->fetchAll(
        'SELECT st.*, s.name as section_name, s.slug as section_slug
         FROM sites st
         JOIN sections s ON st.section_id = s.id
         WHERE st.section_id IN (
             SELECT id FROM sections WHERE path LIKE ?
         ) AND st.status = 1
         ORDER BY st.created_at DESC
         LIMIT ? OFFSET ?',
        [$section['path'] . '%', $perPage, $offset]
    );
}, 300);

render_page($section['name'], $breadcrumbs, function () use ($section, $children, $sites, $siteCount, $currentPage, $totalPages) {
    ?>
    <h2><?= h($section['name']) ?></h2>

    <?php if (!empty($section['description'])): ?>
        <p class="lead text-muted"><?= nl2br(h($section['description'])) ?></p>
    <?php endif; ?>

    <!-- Подразделы -->
    <?php if (!empty($children)): ?>
        <div class="my-4">
            <h5>Подразделы:</h5>
            <div class="subsection-badges">
                <?php foreach ($children as $child): ?>
                    <a href="/section/<?= h($child['slug']) ?>" class="btn btn-outline-primary btn-sm">
                        <?= h($child['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Список сайтов -->
    <div class="mt-4">
        <p class="text-muted">Сайтов в разделе: <strong><?= $siteCount ?></strong></p>

        <?php if (empty($sites)): ?>
            <div class="alert alert-warning">В этом разделе пока нет сайтов.</div>
        <?php else: ?>
            <?php if ($totalPages > 1): ?>
                <p class="small text-muted">Страница <?= $currentPage ?> из <?= $totalPages ?></p>
            <?php endif; ?>

            <ol class="site-list list-group list-group-numbered" start="<?= ($currentPage - 1) * 20 + 1 ?>">
                <?php foreach ($sites as $site): ?>
                    <li class="site-list-item list-group-item">
                        <div class="site-name">
                            <a href="/site/<?= h($site['slug']) ?>"><?= h($site['name']) ?></a>
                        </div>
                        <div class="site-url small text-muted">
                            <?= h($site['url']) ?> &bull; <?= h(date('d.m.Y', strtotime($site['created_at']))) ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </div>

    <!-- Пагинация -->
    <?php if ($totalPages > 1): ?>
        <?= render_pagination($currentPage, $totalPages, '/section/' . $section['slug']) ?>
    <?php endif; ?>
    <?php
});
