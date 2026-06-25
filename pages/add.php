<?php
/**
 * Страница «Добавить сайт» — /add
 */

// Загружаем дерево разделов для селекта
$allSections = $db->fetchAll('SELECT id, parent_id, name, slug, path FROM sections ORDER BY path');
$byParent = [];
foreach ($allSections as $sec) {
    $pid = $sec['parent_id'] ?? 0;
    $byParent[$pid][] = $sec;
}

// Обработка POST
$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        flash_set('error', 'Ошибка безопасности. Пожалуйста, попробуйте снова.');
        header('Location: /add');
        exit;
    }

    $old = [
        'section_id' => $_POST['section_id'] ?? '',
        'name' => $_POST['name'] ?? '',
        'url' => $_POST['url'] ?? '',
        'description' => $_POST['description'] ?? '',
        'email' => $_POST['email'] ?? '',
    ];

    // Серверная валидация
    $errors = validate($_POST, [
        'section_id' => 'required|integer|exists:sections,id',
        'name' => 'required|string|max:512',
        'url' => 'required|url|max:512|unique:sites,url',
        'description' => 'required|string|max:10000',
        'email' => 'nullable|email|max:255',
        'agreement' => 'required|accepted',
    ], $db);

    if (validation_passed($errors)) {
        // Генерация уникального slug
        $slug = generate_slug($old['name']);
        $baseSlug = $slug;
        $counter = 2;
        while ($db->fetchColumn('SELECT COUNT(*) FROM sites WHERE slug = ?', [$slug]) > 0) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        // Очистка описания
        $description = clean_html($old['description']);

        // Сохранение
        $db->insert(
            'INSERT INTO sites (section_id, name, slug, url, description, email, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, 0, NOW())',
            [
                (int) $old['section_id'],
                trim($old['name']),
                $slug,
                trim($old['url']),
                $description,
                !empty($old['email']) ? trim($old['email']) : null,
            ]
        );

        // Инвалидация кэша
        $cache->delete('sites:recent:10');

        flash_set('success', 'Сайт успешно отправлен на модерацию! Он появится в каталоге после проверки.');
        header('Location: /');
        exit;
    }
}

// Выбранное значение (после POST)
$selectedId = isset($old['section_id']) ? (int) $old['section_id'] : null;

// Рекурсивный вывод опций селекта
$renderOptions = function ($parentId, $depth) use (&$renderOptions, $byParent, $selectedId) {
    $html = '';
    $children = $byParent[$parentId] ?? [];
    foreach ($children as $sec) {
        $indent = $depth > 0 ? str_repeat('— ', $depth) : '';
        $sel = ($selectedId === (int) $sec['id']) ? ' selected' : '';
        $html .= '<option value="' . $sec['id'] . '"' . $sel . '>' . $indent . h($sec['name']) . '</option>';
        $html .= $renderOptions($sec['id'], $depth + 1);
    }
    return $html;
};

render_page('Добавить сайт', breadcrumbs_static('Добавить сайт'), function () use ($renderOptions, $errors, $old) {
    ?>
    <h2>Добавить сайт в каталог</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong>Ошибки заполнения:</strong>
            <ul class="mb-0 mt-1">
                <?php foreach ($errors as $err): ?>
                    <li><?= h($err['message']) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="/add" id="add-site-form" novalidate>
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <!-- 1. Раздел сайта -->
        <div class="mb-3">
            <label for="section_id" class="form-label">1. Раздел сайта (обязательно):</label>
            <select name="section_id" id="section_id" class="form-select <?= validation_first_error($errors, 'section_id') ? 'is-invalid' : '' ?>" required>
                <option value="">-- Выберите раздел --</option>
                <?= $renderOptions(0, 0) ?>
            </select>
            <?php if ($err = validation_first_error($errors, 'section_id')): ?>
                <div class="invalid-feedback"><?= h($err) ?></div>
            <?php endif; ?>
        </div>

        <!-- 2. Название сайта -->
        <div class="mb-3">
            <label for="name" class="form-label">2. Название сайта (обязательно):</label>
            <input type="text" name="name" id="name" class="form-control <?= validation_first_error($errors, 'name') ? 'is-invalid' : '' ?>"
                   required value="<?= h($old['name'] ?? '') ?>">
            <?php if ($err = validation_first_error($errors, 'name')): ?>
                <div class="invalid-feedback"><?= h($err) ?></div>
            <?php endif; ?>
        </div>

        <!-- 3. URL сайта -->
        <div class="mb-3">
            <label for="url" class="form-label">URL сайта (обязательно):</label>
            <input type="url" name="url" id="url" class="form-control <?= validation_first_error($errors, 'url') ? 'is-invalid' : '' ?>"
                   maxlength="512" placeholder="https://" required value="<?= h($old['url'] ?? '') ?>">
            <?php if ($err = validation_first_error($errors, 'url')): ?>
                <div class="invalid-feedback"><?= h($err) ?></div>
            <?php endif; ?>
        </div>

        <!-- 4. Описание сайта -->
        <div class="mb-3">
            <label for="description" class="form-label">3. Описание сайта (обязательно):</label>
            <textarea name="description" id="description"
                      class="form-control summernote <?= validation_first_error($errors, 'description') ? 'is-invalid' : '' ?>"
                      maxlength="10000" required rows="5"><?= h($old['description'] ?? '') ?></textarea>
            <?php if ($err = validation_first_error($errors, 'description')): ?>
                <div class="invalid-feedback"><?= h($err) ?></div>
            <?php endif; ?>
        </div>

        <!-- 5. Email -->
        <div class="mb-3">
            <label for="email" class="form-label">4. Email (для связи, необязательно):</label>
            <input type="email" name="email" id="email" class="form-control <?= validation_first_error($errors, 'email') ? 'is-invalid' : '' ?>"
                   maxlength="255" value="<?= h($old['email'] ?? '') ?>">
            <?php if ($err = validation_first_error($errors, 'email')): ?>
                <div class="invalid-feedback"><?= h($err) ?></div>
            <?php endif; ?>
        </div>

        <!-- 6. Согласие с правилами -->
        <div class="mb-3 form-check">
            <input type="checkbox" name="agreement" id="agreement"
                   class="form-check-input <?= validation_first_error($errors, 'agreement') ? 'is-invalid' : '' ?>" required>
            <label for="agreement" class="form-check-label">
                5. Я принимаю условия и согласен с правилами каталога
            </label>
            <?php if ($err = validation_first_error($errors, 'agreement')): ?>
                <div class="invalid-feedback"><?= h($err) ?></div>
            <?php endif; ?>
        </div>

        <!-- 7. Кнопка отправки -->
        <button type="submit" class="btn btn-primary">Отправить на модерацию</button>
    </form>

    <!-- Summernote -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs4.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        $('.summernote').summernote({
            height: 200,
            toolbar: [
                ['style', ['bold', 'italic', 'underline']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link']],
                ['view', ['codeview']]
            ]
        });
    });
    </script>
    <?php
});
