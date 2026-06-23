<?php
/**
 * Главная страница — /
 */

// 1. Корневые разделы с подразделами (кэш 1 час)
$rootSections = $cache->remember('sections:tree', function () use ($db) {
    return $db->fetchAll(
        'SELECT s.*,
                (SELECT COUNT(*) FROM sites WHERE section_id = s.id AND status = 1) as site_count
         FROM sections s
         WHERE s.parent_id IS NULL
         ORDER BY s.id'
    );
}, 3600);

// Подразделы для каждого корневого (тоже кэшируем)
$allChildren = $cache->remember('sections:children:all', function () use ($db) {
    $children = $db->fetchAll(
        'SELECT * FROM sections WHERE parent_id IS NOT NULL ORDER BY name'
    );
    $grouped = [];
    foreach ($children as $child) {
        $grouped[$child['parent_id']][] = $child;
    }
    return $grouped;
}, 3600);

// 2. Последние 10 опубликованных сайтов (кэш 5 минут)
$recentSites = $cache->remember('sites:recent:10', function () use ($db) {
    return $db->fetchAll(
        'SELECT st.*, s.name as section_name, s.slug as section_slug
         FROM sites st
         JOIN sections s ON st.section_id = s.id
         WHERE st.status = 1
         ORDER BY st.created_at DESC
         LIMIT 10'
    );
}, 300);

render_page('Главная', [], function () use ($rootSections, $allChildren, $recentSites) {
    ?>
    <!-- Кнопка Добавить сайт -->
    <div class="row mb-4">
        <div class="col-12">
            <a href="/add" class="btn btn-primary">Добавить сайт</a>
        </div>
    </div>

    <!-- Сетка разделов -->
    <div class="row sections-grid">
        <?php foreach ($rootSections as $section): ?>
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="section-card">
                    <div class="section-title">
                        <a href="/section/<?= h($section['slug']) ?>" class="text-decoration-none">
                            <?= h($section['name']) ?>
                        </a>
                        <?php if ($section['site_count'] > 0): ?>
                            <span class="badge bg-secondary ms-1"><?= $section['site_count'] ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="subsections">
                        <?php
                        $children = $allChildren[$section['id']] ?? [];
                        if ($children):
                            $names = array_map(function ($c) {
                                return '<a href="/section/' . h($c['slug']) . '">' . h($c['name']) . '</a>';
                            }, $children);
                            echo implode(', ', $names);
                        endif;
                        ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Последние 10 сайтов -->
    <?php if (!empty($recentSites)): ?>
    <h3 class="mt-4 mb-3">10 последних сайтов в каталоге</h3>
    <div class="recent-sites">
        <ul class="site-list list-group">
            <?php foreach ($recentSites as $site):
                $desc = strip_tags($site['description'] ?? '');
                $descShort = mb_strlen($desc) > 100 ? mb_substr($desc, 0, 100) . '...' : $desc;
            ?>
                <li class="site-list-item list-group-item px-4">
                    <div>
                        <span class="site-bullet">•</span>
                        <a href="/site/<?= h($site['slug']) ?>"><?= h($site['name']) ?></a>
                        <?php if ($descShort !== ''): ?>
                            — <span class="text-muted small"><?= h($descShort) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="site-meta">
                        <span class="site-url">
                            → <a href="/section/<?= h($site['section_slug']) ?>" class="text-muted">
                                <?= h($site['section_name']) ?>
                            </a>
                        </span>
                        <span class="site-date ms-2">
                            <?= h(date('d.m.Y', strtotime($site['created_at']))) ?>
                        </span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    <?php
});
