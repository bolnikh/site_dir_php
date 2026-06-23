<?php
/**
 * Редактирование раздела — /moderator/sections/edit?id=N
 */

require_moderator();

$sectionId = (int) ($_GET['id'] ?? 0);
if ($sectionId <= 0) {
    http_response_code(404);
    echo '<h1>404 — Раздел не найден</h1>';
    return;
}

// Загрузка раздела
$section = $db->fetch('SELECT * FROM sections WHERE id = ?', [$sectionId]);
if (!$section) {
    http_response_code(404);
    echo '<h1>404 — Раздел не найден</h1>';
    return;
}

// Загрузка всех разделов для селекта
$allSections = $db->fetchAll('SELECT id, parent_id, name, path FROM sections ORDER BY path');
$byParent = [];
foreach ($allSections as $sec) {
    $pid = $sec['parent_id'] ?? 0;
    $byParent[$pid][] = $sec;
}

// Статистика
$siteCount = (int) $db->fetchColumn('SELECT COUNT(*) FROM sites WHERE section_id = ?', [$sectionId]);
$childCount = (int) $db->fetchColumn('SELECT COUNT(*) FROM sections WHERE parent_id = ?', [$sectionId]);

$errors = [];
$old = [
    'parent_id' => (int) ($section['parent_id'] ?? 0),
    'name' => $section['name'],
    'slug' => $section['slug'],
    'description' => $section['description'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'parent_id' => (int) ($_POST['parent_id'] ?? 0),
        'name' => trim($_POST['name'] ?? ''),
        'slug' => trim($_POST['slug'] ?? ''),
        'description' => $_POST['description'] ?? '',
    ];

    if ($old['slug'] === '') {
        $old['slug'] = generate_slug($old['name']);
    }

    // Валидация
    if ($old['name'] === '') {
        $errors[] = ['field' => 'name', 'message' => 'Название обязательно.'];
    }
    if ($old['slug'] === '') {
        $errors[] = ['field' => 'slug', 'message' => 'Slug обязателен.'];
    } else {
        $exists = $db->fetchColumn(
            'SELECT COUNT(*) FROM sections WHERE slug = ? AND id != ?',
            [$old['slug'], $sectionId]
        );
        if ($exists > 0) {
            $errors[] = ['field' => 'slug', 'message' => 'Такой slug уже существует.'];
        }
    }

    if (empty($errors)) {
        $newParentId = $old['parent_id'] > 0 ? $old['parent_id'] : null;
        $oldParentId = $section['parent_id'] ? (int) $section['parent_id'] : null;
        $parentChanged = $newParentId !== $oldParentId;

        // UPDATE
        $db->execute(
            'UPDATE sections SET parent_id = ?, name = ?, slug = ?, description = ?, updated_at = NOW() WHERE id = ?',
            [$newParentId, $old['name'], $old['slug'], clean_html($old['description']), $sectionId]
        );

        // Пересчёт path если изменился родитель
        if ($parentChanged) {
            _recalculatePaths($db, $sectionId, $newParentId);
        }

        // Инвалидация кэша разделов
        $cache->delete('sections:tree');
        $cache->delete('sections:children:all');
        $cache->deletePattern('sections:children:*');

        flash_set('success', 'Раздел «' . $old['name'] . '» обновлён.');
        header('Location: /moderator/sections');
        exit;
    }
}

// Рендер опций (исключаем сам раздел и его потомков)
$descendantIds = _getDescendantIds($db, $sectionId);
$excludeIds = array_merge([$sectionId], $descendantIds);

$renderOptions = function ($parentId, $depth, $selectedId) use (&$renderOptions, $byParent, $excludeIds) {
    $html = '';
    $children = $byParent[$parentId] ?? [];
    foreach ($children as $sec) {
        if (in_array((int) $sec['id'], $excludeIds)) continue;
        $indent = $depth > 0 ? str_repeat('— ', $depth) : '';
        $sel = ($selectedId === (int) $sec['id']) ? ' selected' : '';
        $html .= '<option value="' . $sec['id'] . '"' . $sel . '>' . $indent . h($sec['name']) . '</option>';
        $html .= $renderOptions($sec['id'], $depth + 1, $selectedId);
    }
    return $html;
};

// Текущий путь
$pathNames = [];
$pathIds = array_filter(explode('/', $section['path']));
if (!empty($pathIds)) {
    $placeholders = implode(',', array_fill(0, count($pathIds), '?'));
    $ancestors = $db->fetchAll(
        "SELECT name FROM sections WHERE id IN ({$placeholders}) ORDER BY id",
        $pathIds
    );
    $pathNames = array_column($ancestors, 'name');
}
$currentPath = implode(' / ', $pathNames);

render_page('Редактировать раздел', breadcrumbs_generate([
    ['label' => 'Модерация', 'url' => '/moderator/list'],
    ['label' => 'Разделы', 'url' => '/moderator/sections'],
    ['label' => $section['name'], 'url' => null],
]), function () use ($renderOptions, $old, $errors, $sectionId, $currentPath, $siteCount, $childCount) {
    ?>
    <h2>Редактировать раздел</h2>

    <p class="text-muted">Путь: <?= h($currentPath) ?></p>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?><li><?= h($e['message']) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="/moderator/sections/edit?id=<?= $sectionId ?>">
        <div class="mb-3">
            <label for="parent_id" class="form-label">Родительский раздел:</label>
            <select name="parent_id" id="parent_id" class="form-select">
                <option value="0">-- Корневой раздел --</option>
                <?= $renderOptions(0, 0, $old['parent_id']) ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="name" class="form-label">Название:</label>
            <input type="text" name="name" id="name" class="form-control" maxlength="512" required
                   value="<?= h($old['name']) ?>">
        </div>

        <div class="mb-3">
            <label for="slug" class="form-label">Slug:</label>
            <input type="text" name="slug" id="slug" class="form-control" maxlength="255" required
                   value="<?= h($old['slug']) ?>">
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Описание:</label>
            <textarea name="description" id="description" class="form-control summernote" rows="4"><?= h($old['description']) ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">💾 СОХРАНИТЬ ИЗМЕНЕНИЯ</button>
        <a href="/moderator/sections" class="btn btn-outline-secondary">Отмена</a>
    </form>

    <!-- Опасная зона: удаление -->
    <hr class="my-4">
    <div class="card border-danger">
        <div class="card-header bg-danger text-white"><strong>⚠️ Опасная зона</strong></div>
        <div class="card-body">
            <p>Раздел содержит: <strong><?= $childCount ?></strong> подразделов, <strong><?= $siteCount ?></strong> сайтов.</p>
            <?php if ($siteCount == 0 && $childCount == 0): ?>
                <form method="POST" action="/moderator/sections/delete"
                      onsubmit="return confirm('Точно удалить раздел «<?= h($old['name']) ?>»?')">
                    <input type="hidden" name="id" value="<?= $sectionId ?>">
                    <button type="submit" class="btn btn-danger">🗑️ УДАЛИТЬ РАЗДЕЛ</button>
                </form>
            <?php else: ?>
                <button class="btn btn-danger" disabled>🗑️ УДАЛИТЬ РАЗДЕЛ (недоступно — есть содержимое)</button>
            <?php endif; ?>
        </div>
    </div>

    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs4.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        $('.summernote').summernote({ height: 150,
            toolbar: [['style', ['bold','italic','underline']], ['para', ['ul','ol','paragraph']], ['insert', ['link']], ['view', ['codeview']]]
        });
    });
    </script>
    <?php
});

/** Получить ID всех потомков раздела */
function _getDescendantIds(\App\Database $db, int $sectionId): array
{
    $section = $db->fetch('SELECT path FROM sections WHERE id = ?', [$sectionId]);
    if (!$section) return [];
    $rows = $db->fetchAll(
        "SELECT id FROM sections WHERE path LIKE ? AND id != ?",
        [$section['path'] . '/%', $sectionId]
    );
    return array_column($rows, 'id');
}

/** Пересчёт path при смене родителя */
function _recalculatePaths(\App\Database $db, int $sectionId, ?int $newParentId): void
{
    // Сначала обновить сам раздел
    if ($newParentId) {
        $parentPath = $db->fetchColumn('SELECT path FROM sections WHERE id = ?', [$newParentId]);
        $newPath = $parentPath . '/' . $sectionId;
    } else {
        $newPath = (string) $sectionId;
    }

    $oldSection = $db->fetch('SELECT path FROM sections WHERE id = ?', [$sectionId]);
    $oldPath = $oldSection['path'] ?? '';

    $db->execute('UPDATE sections SET path = ? WHERE id = ?', [$newPath, $sectionId]);

    // Обновить всех потомков: заменить старый префикс пути на новый
    if ($oldPath && $oldPath !== $newPath) {
        $descendants = $db->fetchAll(
            "SELECT id, path FROM sections WHERE path LIKE ?",
            [$oldPath . '/%']
        );
        foreach ($descendants as $desc) {
            $updatedPath = $newPath . substr($desc['path'], strlen($oldPath));
            $db->execute('UPDATE sections SET path = ? WHERE id = ?', [$updatedPath, $desc['id']]);
        }
    }
}
