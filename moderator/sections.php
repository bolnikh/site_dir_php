<?php
/**
 * Управление разделами — /moderator/sections
 * + обработка удаления: /moderator/sections/delete (POST)
 */

require_moderator();

// Обработка удаления
if (($_GET['page'] ?? '') === 'moderator/sections/delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $deleteId = (int) ($_POST['id'] ?? 0);
    if ($deleteId > 0) {
        // Проверка: нет сайтов и дочерних разделов
        $siteCount = (int) $db->fetchColumn(
            'SELECT COUNT(*) FROM sites WHERE section_id = ?', [$deleteId]
        );
        $childCount = (int) $db->fetchColumn(
            'SELECT COUNT(*) FROM sections WHERE parent_id = ?', [$deleteId]
        );

        if ($siteCount > 0) {
            flash_set('error', 'Нельзя удалить раздел: в нём есть сайты (' . $siteCount . ' шт.).');
        } elseif ($childCount > 0) {
            flash_set('error', 'Нельзя удалить раздел: в нём есть подразделы (' . $childCount . ' шт.).');
        } else {
            $db->execute('DELETE FROM sections WHERE id = ?', [$deleteId]);
            _invalidateSectionsCache($cache);
            flash_set('success', 'Раздел удалён.');
        }
    }
    header('Location: /moderator/sections');
    exit;
}

// Фильтр
$search = trim($_GET['search'] ?? '');

// Загрузка разделов
if ($search !== '') {
    // Поиск по имени + родители для показа полного пути
    $sections = $db->fetchAll(
        "SELECT s.*,
                (SELECT COUNT(*) FROM sites WHERE section_id = s.id) as site_count
         FROM sections s
         WHERE s.name ILIKE ?
            OR s.id IN (
                SELECT parent_id FROM sections WHERE name ILIKE ?
            )
         ORDER BY s.path",
        ['%' . $search . '%', '%' . $search . '%']
    );
} else {
    $sections = $db->fetchAll(
        "SELECT s.*,
                (SELECT COUNT(*) FROM sites WHERE section_id = s.id) as site_count
         FROM sections s
         ORDER BY s.path"
    );
}

// Построение дерева для отображения
$byId = [];
foreach ($sections as &$sec) {
    $byId[$sec['id']] = &$sec;
}
unset($sec);

render_page('Управление разделами', breadcrumbs_generate([
    ['label' => 'Модерация', 'url' => '/moderator/list'],
    ['label' => 'Разделы', 'url' => null],
]), function () use ($sections, $search) {
    ?>
    <!-- Навигация -->
    <div class="d-flex gap-2 mb-3">
        <a href="/moderator/list" class="btn btn-outline-secondary btn-sm">Список на модерацию</a>
        <a href="/moderator/sections" class="btn btn-outline-primary btn-sm active">Управление разделами</a>
        <a href="/moderator/sections/add" class="btn btn-success btn-sm">+ Добавить раздел</a>
        <a href="/moderator/logout" class="btn btn-outline-danger btn-sm ms-auto">Выход</a>
    </div>

    <h2>📂 УПРАВЛЕНИЕ РАЗДЕЛАМИ</h2>

    <!-- Фильтр -->
    <form method="GET" action="/moderator/sections" class="row g-2 mb-4">
        <div class="col-sm-8 col-md-6">
            <input type="text" name="search" class="form-control"
                   placeholder="Поиск по названию..." value="<?= h($search) ?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Найти</button>
            <?php if ($search !== ''): ?>
                <a href="/moderator/sections" class="btn btn-outline-secondary">Сбросить</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Дерево разделов -->
    <div class="section-tree">
        <?php foreach ($sections as $sec):
            $depth = count(array_filter(explode('/', $sec['path']))) - 1;
            $indent = $depth * 2;
        ?>
            <div class="tree-item" style="padding-left: <?= $indent ?>rem;">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="tree-icon"><?= $depth > 0 ? '├─' : '┌─' ?></span>
                    <strong><?= h($sec['name']) ?></strong>
                    <span class="text-muted small">(<?= $sec['site_count'] ?> сайтов)</span>

                    <a href="/moderator/sections/edit?id=<?= $sec['id'] ?>"
                       class="btn btn-outline-secondary btn-sm">✏️ ред.</a>
                    <a href="/moderator/sections/add?parent_id=<?= $sec['id'] ?>"
                       class="btn btn-outline-success btn-sm">➕ подраздел</a>
                    <?php if ($sec['site_count'] == 0): ?>
                        <form method="POST" action="/moderator/sections/delete" class="d-inline"
                              onsubmit="return confirm('Удалить раздел «<?= h($sec['name']) ?>»?')">
                            <input type="hidden" name="id" value="<?= $sec['id'] ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm">🗑️ удал.</button>
                        </form>
                    <?php else: ?>
                        <button class="btn btn-outline-danger btn-sm" disabled
                                title="Нельзя удалить: в разделе есть сайты">🗑️ удал.</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
});

function _invalidateSectionsCache(\App\Cache $cache): void
{
    $cache->delete('sections:tree');
    $cache->delete('sections:children:all');
    $cache->deletePattern('sections:children:*');
}
