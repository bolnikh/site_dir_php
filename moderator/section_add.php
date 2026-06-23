<?php
/**
 * Добавление раздела — /moderator/sections/add
 */

require_moderator();

// Загрузка разделов для селекта родителя
$allSections = $db->fetchAll('SELECT id, parent_id, name, path FROM sections ORDER BY path');
$byParent = [];
foreach ($allSections as $sec) {
    $pid = $sec['parent_id'] ?? 0;
    $byParent[$pid][] = $sec;
}

$preselectedParent = (int) ($_GET['parent_id'] ?? 0);
$errors = [];
$old = ['parent_id' => $preselectedParent, 'name' => '', 'slug' => '', 'description' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'parent_id' => (int) ($_POST['parent_id'] ?? 0),
        'name' => trim($_POST['name'] ?? ''),
        'slug' => trim($_POST['slug'] ?? ''),
        'description' => $_POST['description'] ?? '',
    ];

    // Автогенерация slug если пустой
    if ($old['slug'] === '') {
        $old['slug'] = generate_slug($old['name']);
    }

    // Валидация
    if ($old['name'] === '') {
        $errors[] = ['field' => 'name', 'message' => 'Название обязательно.'];
    }
    if ($old['slug'] === '') {
        $errors[] = ['field' => 'slug', 'message' => 'Slug обязателен.'];
    } elseif ($db->fetchColumn('SELECT COUNT(*) FROM sections WHERE slug = ?', [$old['slug']]) > 0) {
        $errors[] = ['field' => 'slug', 'message' => 'Такой slug уже существует.'];
    }

    if (empty($errors)) {
        $parentId = $old['parent_id'] > 0 ? $old['parent_id'] : null;

        // INSERT
        $newId = $db->insert(
            'INSERT INTO sections (parent_id, path, name, slug, description) VALUES (?, ?, ?, ?, ?)',
            [$parentId, '', $old['name'], $old['slug'], clean_html($old['description'])]
        );

        // Обновить path
        if ($parentId) {
            $parentPath = $db->fetchColumn('SELECT path FROM sections WHERE id = ?', [$parentId]);
            $newPath = $parentPath . '/' . $newId;
        } else {
            $newPath = (string) $newId;
        }
        $db->execute('UPDATE sections SET path = ? WHERE id = ?', [$newPath, $newId]);

        // Инвалидация кэша разделов
        $cache->delete('sections:tree');
        $cache->delete('sections:children:all');
        $cache->deletePattern('sections:children:*');

        flash_set('success', 'Раздел «' . $old['name'] . '» добавлен.');
        header('Location: /moderator/sections');
        exit;
    }
}

// Рендер опций селекта
$renderOptions = function ($parentId, $depth, $selectedId) use (&$renderOptions, $byParent) {
    $html = '';
    $children = $byParent[$parentId] ?? [];
    foreach ($children as $sec) {
        $indent = $depth > 0 ? str_repeat('— ', $depth) : '';
        $sel = ($selectedId === (int) $sec['id']) ? ' selected' : '';
        $html .= '<option value="' . $sec['id'] . '"' . $sel . '>' . $indent . h($sec['name']) . '</option>';
        $html .= $renderOptions($sec['id'], $depth + 1, $selectedId);
    }
    return $html;
};

render_page('Добавить раздел', breadcrumbs_generate([
    ['label' => 'Модерация', 'url' => '/moderator/list'],
    ['label' => 'Разделы', 'url' => '/moderator/sections'],
    ['label' => 'Добавить', 'url' => null],
]), function () use ($renderOptions, $old, $errors) {
    ?>
    <h2>Добавить раздел</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?= h($e['message']) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="/moderator/sections/add">
        <div class="mb-3">
            <label for="parent_id" class="form-label">Родительский раздел (пусто = корневой):</label>
            <select name="parent_id" id="parent_id" class="form-select">
                <option value="0">-- Корневой раздел --</option>
                <?= $renderOptions(0, 0, $old['parent_id']) ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="name" class="form-label">Название раздела:</label>
            <input type="text" name="name" id="name" class="form-control" maxlength="512" required
                   value="<?= h($old['name']) ?>"
                   oninput="document.getElementById('slug').value = transliterate(this.value)">
        </div>

        <div class="mb-3">
            <label for="slug" class="form-label">Slug (ЧПУ):</label>
            <input type="text" name="slug" id="slug" class="form-control" maxlength="255" required
                   value="<?= h($old['slug']) ?>">
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Описание:</label>
            <textarea name="description" id="description" class="form-control summernote" rows="4"><?= h($old['description']) ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">💾 СОХРАНИТЬ РАЗДЕЛ</button>
        <a href="/moderator/sections" class="btn btn-outline-secondary">Отмена</a>
        <a href="/moderator/contact_us" class="btn btn-outline-secondary">📬 Сообщения</a>
    </form>

    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs4.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        $('.summernote').summernote({ height: 150,
            toolbar: [['style', ['bold','italic','underline']], ['para', ['ul','ol','paragraph']], ['insert', ['link']], ['view', ['codeview']]]
        });
    });
    // Простая транслитерация для slug (на клиенте)
    function transliterate(text) {
        var map = { 'а':'a','б':'b','в':'v','г':'g','д':'d','е':'e','ё':'yo','ж':'zh','з':'z','и':'i','й':'y','к':'k','л':'l','м':'m','н':'n','о':'o','п':'p','р':'r','с':'s','т':'t','у':'u','ф':'f','х':'h','ц':'ts','ч':'ch','ш':'sh','щ':'sch','ъ':'','ы':'y','ь':'','э':'e','ю':'yu','я':'ya','А':'a','Б':'b','В':'v','Г':'g','Д':'d','Е':'e','Ё':'yo','Ж':'zh','З':'z','И':'i','Й':'y','К':'k','Л':'l','М':'m','Н':'n','О':'o','П':'p','Р':'r','С':'s','Т':'t','У':'u','Ф':'f','Х':'h','Ц':'ts','Ч':'ch','Ш':'sh','Щ':'sch','Ъ':'','Ы':'y','Ь':'','Э':'e','Ю':'yu','Я':'ya' };
        return text.replace(/[а-яА-ЯёЁ]/g, function(c) { return map[c] || c; }).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    }
    </script>
    <?php
});
