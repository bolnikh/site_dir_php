<?php
/**
 * Шапка сайта
 *
 * Переменные:
 * @var array  $appConfig  — конфигурация приложения
 * @var ?array $currentUser — данные текущего пользователя (null если не авторизован)
 */
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? h($pageTitle) . ' — ' : '' ?><?= h($appConfig['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>

<header class="bg-primary text-white py-3 mb-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col">
                <a href="/" class="text-white text-decoration-none">
                    <h1 class="h4 mb-0"><?= h($appConfig['name']) ?></h1>
                </a>
            </div>
            <div class="col-auto">
                <?php if (!empty($currentUser)): ?>
                    <span class="me-3">👤 <?= h($currentUser['username']) ?></span>
                    <a href="/moderator/logout" class="btn btn-outline-light btn-sm">Выйти</a>
                <?php else: ?>
                    <a href="/add" class="btn btn-light">Добавить сайт</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<?php if (!empty($breadcrumbs)): ?>
<nav aria-label="breadcrumb" class="container">
    <ol class="breadcrumb">
        <?php foreach ($breadcrumbs as $crumb): ?>
            <?php if ($crumb['url'] !== null): ?>
                <li class="breadcrumb-item"><a href="<?= h($crumb['url']) ?>"><?= h($crumb['title']) ?></a></li>
            <?php else: ?>
                <li class="breadcrumb-item active" aria-current="page"><?= h($crumb['title']) ?></li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ol>
</nav>
<?php endif; ?>

<main class="container">
    <?php foreach (flash_get() as $msg): ?>
        <div class="alert alert-<?= h($msg['type']) ?> alert-dismissible fade show" role="alert">
            <?= h($msg['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрыть"></button>
        </div>
    <?php endforeach; ?>
