<?php
/**
 * Список сайтов на модерации — /moderator/list
 */

require_moderator();

// Параметры
$search = trim($_GET['search'] ?? '');
$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$perPage = $appConfig['sites_per_page'] ?? 20;

// Подсчёт сайтов на модерации
if ($search !== '') {
    $totalItems = (int) $db->fetchColumn(
        "SELECT COUNT(*) FROM sites WHERE status = 0 AND name ILIKE ?",
        ['%' . $search . '%']
    );
} else {
    $totalItems = (int) $db->fetchColumn('SELECT COUNT(*) FROM sites WHERE status = 0');
}

$totalPages = max(1, (int) ceil($totalItems / $perPage));
$currentPage = min($currentPage, $totalPages);

// Список сайтов
$offset = ($currentPage - 1) * $perPage;
if ($search !== '') {
    $sites = $db->fetchAll(
        'SELECT st.*, s.name as section_name, s.slug as section_slug
         FROM sites st
         JOIN sections s ON st.section_id = s.id
         WHERE st.status = 0 AND st.name ILIKE ?
         ORDER BY st.created_at DESC
         LIMIT ? OFFSET ?',
        ['%' . $search . '%', $perPage, $offset]
    );
} else {
    $sites = $db->fetchAll(
        'SELECT st.*, s.name as section_name, s.slug as section_slug
         FROM sites st
         JOIN sections s ON st.section_id = s.id
         WHERE st.status = 0
         ORDER BY st.created_at DESC
         LIMIT ? OFFSET ?',
        [$perPage, $offset]
    );
}

render_page('Сайты на модерации', breadcrumbs_generate([
    ['label' => 'Модерация', 'url' => null],
]), function () use ($sites, $search, $currentPage, $totalPages, $totalItems) {
    ?>
    <!-- Навигация модератора -->
    <div class="d-flex gap-2 mb-3">
        <a href="/moderator/list" class="btn btn-outline-primary btn-sm active">Список на модерацию</a>
        <a href="/moderator/sections" class="btn btn-outline-secondary btn-sm">Управление разделами</a>
        <a href="/moderator/logout" class="btn btn-outline-danger btn-sm ms-auto">Выход</a>
    </div>

    <h2>🛡️ МОДЕРАЦИЯ — СПИСОК САЙТОВ</h2>

    <!-- Поиск -->
    <form method="GET" action="/moderator/list" class="row g-2 mb-4">
        <div class="col-sm-8 col-md-6">
            <input type="text" name="search" class="form-control"
                   placeholder="Поиск по названию..." value="<?= h($search) ?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Поиск</button>
            <?php if ($search !== ''): ?>
                <a href="/moderator/list" class="btn btn-outline-secondary">Сброс</a>
            <?php endif; ?>
        </div>
    </form>

    <p class="text-muted">Всего на модерации: <strong><?= $totalItems ?></strong></p>

    <?php if (empty($sites)): ?>
        <div class="alert alert-info">Нет сайтов, ожидающих модерации.</div>
    <?php else: ?>
        <?php if ($totalPages > 1): ?>
            <p class="small text-muted">Страница <?= $currentPage ?> из <?= $totalPages ?></p>
        <?php endif; ?>

        <ol class="site-list list-group list-group-numbered"
            start="<?= ($currentPage - 1) * $perPage + 1 ?>">
            <?php foreach ($sites as $site): ?>
                <li class="site-list-item list-group-item">
                    <div class="site-name">
                        <a href="/moderator/moderate?id=<?= $site['id'] ?>">
                            <?= h($site['name']) ?>
                        </a>
                    </div>
                    <div class="site-meta small text-muted">
                        → <?= h($site['section_name']) ?> &bull;
                        <?= h(date('d.m.Y', strtotime($site['created_at']))) ?> &bull;
                        <span class="text-warning">ожидает проверки</span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>

    <!-- Пагинация -->
    <?php if ($totalPages > 1): ?>
        <?= render_pagination($currentPage, $totalPages, '/moderator/list',
            $search !== '' ? ['search' => $search] : []) ?>
    <?php endif; ?>
    <?php
});
