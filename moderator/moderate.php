<?php
/**
 * Модерация отдельного сайта — /moderator/moderate?id=N
 */

require_moderator();

$siteId = (int) ($_GET['id'] ?? 0);
if ($siteId <= 0) {
    http_response_code(404);
    echo '<h1>404 — Сайт не найден</h1>';
    return;
}

// Загружаем сайт
$site = $db->fetch(
    'SELECT st.*, s.name as section_name, s.slug as section_slug,
            s.path as section_path
     FROM sites st
     JOIN sections s ON st.section_id = s.id
     WHERE st.id = ?',
    [$siteId]
);

if (!$site) {
    http_response_code(404);
    render_page('404', breadcrumbs_static('Сайт не найден'), function () {
        echo '<h1>404 — Сайт не найден</h1>';
    });
    return;
}

// Загружаем дерево разделов для селектов
$allSections = $db->fetchAll('SELECT id, parent_id, name, path FROM sections ORDER BY path');
$byParent = [];
foreach ($allSections as $sec) {
    $pid = $sec['parent_id'] ?? 0;
    $byParent[$pid][] = $sec;
}

// Обработка действий
$action = $_POST['action'] ?? null;
$postError = null;

if ($action && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $postError = _handleModerationAction($action, $site, $db, $cache, $mailer, $byParent);
}

// Рекурсивный рендер опций селекта
$renderSectionOptions = function ($parentId, $depth, $selectedId) use (&$renderSectionOptions, $byParent) {
    $html = '';
    $children = $byParent[$parentId] ?? [];
    foreach ($children as $sec) {
        $indent = $depth > 0 ? str_repeat('— ', $depth) : '';
        $sel = ((int) $sec['id'] === $selectedId) ? ' selected' : '';
        $html .= '<option value="' . $sec['id'] . '"' . $sel . '>' . $indent . h($sec['name']) . '</option>';
        $html .= $renderSectionOptions($sec['id'], $depth + 1, $selectedId);
    }
    return $html;
};

// Статусы для отображения
$statusLabels = [
    0 => ['text' => 'ожидает проверки', 'class' => 'text-warning'],
    1 => ['text' => 'опубликован', 'class' => 'text-success'],
    2 => ['text' => 'отклонён', 'class' => 'text-danger'],
];
$statusInfo = $statusLabels[$site['status']] ?? $statusLabels[0];

render_page('Модерация сайта', breadcrumbs_generate([
    ['label' => 'Модерация', 'url' => '/moderator/list'],
    ['label' => 'Сайт #' . $siteId, 'url' => null],
]), function () use ($site, $siteId, $renderSectionOptions, $statusInfo, $postError, $action) {
    ?>
    <h2>🛡️ МОДЕРАЦИЯ САЙТА</h2>

    <?php if ($postError): ?>
        <div class="alert alert-danger"><?= h($postError) ?></div>
    <?php endif; ?>

    <!-- Блок: Данные сайта -->
    <div class="card mb-4">
        <div class="card-header">
            <strong>Данные сайта</strong>
            <span class="ms-2 <?= $statusInfo['class'] ?>">(<?= $statusInfo['text'] ?>)</span>
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Раздел:</dt>
                <dd class="col-sm-9"><?= h($site['section_name']) ?></dd>

                <dt class="col-sm-3">Название сайта:</dt>
                <dd class="col-sm-9"><?= h($site['name']) ?></dd>

                <dt class="col-sm-3">URL сайта:</dt>
                <dd class="col-sm-9"><a href="<?= h($site['url']) ?>" target="_blank" rel="nofollow noopener"><?= h($site['url']) ?></a></dd>

                <dt class="col-sm-3">Дата добавления:</dt>
                <dd class="col-sm-9"><?= h(date('d.m.Y H:i', strtotime($site['created_at']))) ?></dd>

                <dt class="col-sm-3">Email автора:</dt>
                <dd class="col-sm-9"><?= $site['email'] ? h($site['email']) : '<span class="text-muted">не указан</span>' ?></dd>

                <dt class="col-sm-3">Описание:</dt>
                <dd class="col-sm-9">
                    <div class="border rounded p-3 bg-light">
                        <?= $site['description'] ? clean_html($site['description']) : '<span class="text-muted">нет описания</span>' ?>
                    </div>
                </dd>
            </dl>
        </div>
    </div>

    <!-- Блок: Действия модератора -->
    <?php if ($site['status'] === 0): ?>
    <div class="card mb-4">
        <div class="card-header"><strong>Действия модератора</strong></div>
        <div class="card-body">
            <div class="d-flex gap-3">
                <form method="POST" action="/moderator/moderate?id=<?= $siteId ?>" class="d-inline"
                      onsubmit="return confirm('Одобрить сайт и опубликовать?')">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="btn btn-success">✅ ОДОБРИТЬ (опубликовать сайт)</button>
                </form>

                <form method="POST" action="/moderator/moderate?id=<?= $siteId ?>" class="d-inline"
                      onsubmit="return confirm('Отклонить сайт?')">
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="btn btn-danger">❌ ОТКЛОНИТЬ (отклонить сайт)</button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Блок: Редактирование сайта -->
    <div class="card mb-4">
        <div class="card-header"><strong>Редактирование сайта</strong></div>
        <div class="card-body">
            <form method="POST" action="/moderator/moderate?id=<?= $siteId ?>"
                  onsubmit="return confirm('Сохранить изменения и опубликовать сайт?')">
                <input type="hidden" name="action" value="edit_and_publish">

                <div class="mb-3">
                    <label for="edit_section_id" class="form-label">Раздел сайта:</label>
                    <select name="section_id" id="edit_section_id" class="form-select">
                        <option value="">-- Выберите раздел --</option>
                        <?= $renderSectionOptions(0, 0, (int) $site['section_id']) ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="edit_name" class="form-label">Название сайта:</label>
                    <input type="text" name="name" id="edit_name" class="form-control"
                           maxlength="512" required value="<?= h($site['name']) ?>">
                </div>

                <div class="mb-3">
                    <label for="edit_description" class="form-label">Описание сайта:</label>
                    <textarea name="description" id="edit_description"
                              class="form-control summernote" rows="5"><?= h($site['description']) ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">💾 СОХРАНИТЬ И ОПУБЛИКОВАТЬ</button>
            </form>
        </div>
    </div>

    <p>
        <a href="/moderator/list" class="btn btn-outline-secondary">← Назад к списку</a>
        <a href="/moderator/contact_us" class="btn btn-outline-secondary">📬 Сообщения</a>
    </p>

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

/**
 * Обработка действий модерации.
 */
function _handleModerationAction(
    string $action,
    array $site,
    \App\Database $db,
    \App\Cache $cache,
    \App\Mailer $mailer,
    array $byParent
): ?string {
    switch ($action) {
        case 'approve':
            $db->execute(
                'UPDATE sites SET status = 1, moderated_at = NOW() WHERE id = ?',
                [(int) $site['id']]
            );
            _invalidateSiteCache($cache, $site);

            if (!empty($site['email'])) {
                $mailer->sendSiteApproved(
                    $site['email'],
                    $site['name'],
                    (int) $site['id'],
                    $appConfig['url']
                );
            }

            flash_set('success', 'Сайт "' . $site['name'] . '" одобрен и опубликован!');
            header('Location: /moderator/list');
            exit;

        case 'reject':
            $db->execute(
                'UPDATE sites SET status = 2, moderated_at = NOW() WHERE id = ?',
                [(int) $site['id']]
            );
            _invalidateSiteCache($cache, $site);

            if (!empty($site['email'])) {
                $mailer->sendSiteRejected(
                    $site['email'],
                    $site['name'],
                    'catalog@homecatalog.ru'
                );
            }

            flash_set('success', 'Сайт "' . $site['name'] . '" отклонён.');
            header('Location: /moderator/list');
            exit;

        case 'edit_and_publish':
            $sectionId = (int) ($_POST['section_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = $_POST['description'] ?? '';

            if ($sectionId <= 0 || $name === '' || $description === '') {
                return 'Заполните все обязательные поля (раздел, название, описание).';
            }

            $cleanDesc = clean_html($description);

            $db->execute(
                'UPDATE sites SET section_id = ?, name = ?, description = ?,
                 status = 1, moderated_at = NOW() WHERE id = ?',
                [$sectionId, $name, $cleanDesc, (int) $site['id']]
            );
            _invalidateSiteCache($cache, $site);
            // Если раздел изменился — инвалидируем и новый раздел
            if ($sectionId !== (int) $site['section_id']) {
                $cache->delete('sites:count:section:' . $sectionId);
                $cache->deletePattern('sites:section:' . $sectionId . ':page:*');
            }

            flash_set('success', 'Сайт "' . $name . '" сохранён и опубликован!');
            header('Location: /moderator/list');
            exit;

        default:
            return 'Неизвестное действие.';
    }

    return null;
}

/**
 * Инвалидация кэша после изменения сайта.
 */
function _invalidateSiteCache(\App\Cache $cache, array $site): void
{
    $cache->delete('sites:recent:10');
    $cache->delete('site:' . $site['slug']);
    $cache->deletePattern('sites:section:' . $site['section_id'] . ':page:*');
    // Также инвалидируем счётчики и кэш всех страниц раздела
    $cache->delete('sites:count:section:' . $site['section_id']);
}
