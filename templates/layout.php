<?php

/**
 * Общий шаблон страницы — render_page()
 *
 * @param string   $title       Заголовок страницы (для <title>)
 * @param array    $breadcrumbs Хлебные крошки [['label' => '...', 'url' => '...|null'], ...]
 * @param callable $content     Callback, который выводит HTML основного блока
 */
function render_page(string $title, array $breadcrumbs, callable $content): void
{
    require __DIR__ . '/header.php';
    ?>
    <!-- Flash messages -->
    <div class="container mt-3">
        <?php foreach (flash_get() as $msg): ?>
            <div class="alert alert-<?= h($msg['type']) ?> alert-dismissible fade show" role="alert">
                <?= h($msg['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрыть"></button>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Breadcrumbs -->
    <?php if (!empty($breadcrumbs)): ?>
    <nav class="container mt-2" aria-label="breadcrumb">
        <ol class="breadcrumb">
            <?php $last = array_key_last($breadcrumbs); ?>
            <?php foreach ($breadcrumbs as $i => $crumb): ?>
                <?php if ($i === $last): ?>
                    <li class="breadcrumb-item active" aria-current="page"><?= h($crumb['label']) ?></li>
                <?php else: ?>
                    <li class="breadcrumb-item"><a href="<?= h($crumb['url']) ?>"><?= h($crumb['label']) ?></a></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?php endif; ?>

    <!-- Main content -->
    <main class="container mt-3">
        <?php $content(); ?>
    </main>

    <?php
    require __DIR__ . '/footer.php';
}
