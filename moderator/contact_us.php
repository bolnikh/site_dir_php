<?php
/**
 * Сообщения от пользователей — /moderator/contact_us
 */

require_moderator();

// Обработка действий
$action = $_POST['action'] ?? null;
if ($action && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $msgId = (int) ($_POST['id'] ?? 0);

    if ($msgId > 0) {
        switch ($action) {
            case 'mark_read':
                $db->execute('UPDATE contact_us SET is_read = 1 WHERE id = ?', [$msgId]);
                flash_set('success', 'Сообщение отмечено как прочитанное.');
                break;
            case 'delete':
                $db->execute('DELETE FROM contact_us WHERE id = ?', [$msgId]);
                flash_set('success', 'Сообщение удалено.');
                break;
        }
    }
    header('Location: /moderator/contact_us');
    exit;
}

// Пагинация
$currentPage = max(1, (int) ($_GET['pg'] ?? 1));
$perPage = $appConfig['sites_per_page'] ?? 20;
$totalItems = (int) $db->fetchColumn('SELECT COUNT(*) FROM contact_us');
$totalPages = max(1, (int) ceil($totalItems / $perPage));
$currentPage = min($currentPage, $totalPages);

$offset = ($currentPage - 1) * $perPage;
$messages = $db->fetchAll(
    'SELECT * FROM contact_us ORDER BY created_at DESC LIMIT ? OFFSET ?',
    [$perPage, $offset]
);

render_page('Сообщения', breadcrumbs_generate([
    ['label' => 'Модерация', 'url' => '/moderator/list'],
    ['label' => 'Сообщения', 'url' => null],
]), function () use ($messages, $currentPage, $totalPages, $totalItems) {
    ?>
    <!-- Навигация -->
    <div class="d-flex gap-2 mb-3">
        <a href="/moderator/list" class="btn btn-outline-secondary btn-sm">Список на модерацию</a>
        <a href="/moderator/sections" class="btn btn-outline-secondary btn-sm">Управление разделами</a>
        <a href="/moderator/contact_us" class="btn btn-outline-primary btn-sm active">📬 Сообщения</a>
        <a href="/moderator/logout" class="btn btn-outline-danger btn-sm ms-auto">Выход</a>
    </div>

    <h2>📬 СООБЩЕНИЯ ОТ ПОЛЬЗОВАТЕЛЕЙ</h2>

    <p class="text-muted">Всего сообщений: <strong><?= $totalItems ?></strong></p>

    <?php if (empty($messages)): ?>
        <div class="alert alert-info">Нет сообщений.</div>
    <?php else: ?>
        <?php if ($totalPages > 1): ?>
            <p class="small text-muted">Страница <?= $currentPage ?> из <?= $totalPages ?></p>
        <?php endif; ?>

        <?php foreach ($messages as $msg): ?>
            <div class="card mb-3 <?= $msg['is_read'] ? 'border-secondary' : 'border-danger' ?>">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <strong><?= h(date('d.m.Y H:i', strtotime($msg['created_at']))) ?></strong>
                        <?php if ($msg['is_read']): ?>
                            <span class="badge bg-secondary ms-2">прочитано</span>
                        <?php else: ?>
                            <span class="badge bg-danger ms-2">новое</span>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-1">
                        <?php if (!$msg['is_read']): ?>
                            <form method="POST" action="/moderator/contact_us" class="d-inline">
                                <input type="hidden" name="action" value="mark_read">
                                <input type="hidden" name="id" value="<?= $msg['id'] ?>">
                                <button type="submit" class="btn btn-outline-success btn-sm">✓ Прочитано</button>
                            </form>
                        <?php endif; ?>
                        <form method="POST" action="/moderator/contact_us" class="d-inline"
                              onsubmit="return confirm('Удалить сообщение?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $msg['id'] ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm">🗑️ Удалить</button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <p class="mb-1">
                        <strong>Email:</strong>
                        <a href="#" onclick="var e=this.textContent;this.href='mai'+'lto:'+e"><?= h($msg['email']) ?></a>
                    </p>
                    <?php if (!empty($msg['phone'])): ?>
                        <p class="mb-1"><strong>Телефон:</strong> <?= h($msg['phone']) ?></p>
                    <?php endif; ?>
                    <hr>
                    <p class="mb-0" style="white-space: pre-wrap;"><?= h($msg['message']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($totalPages > 1): ?>
        <?= render_pagination($currentPage, $totalPages, '/moderator/contact_us') ?>
    <?php endif; ?>
    <?php
});
