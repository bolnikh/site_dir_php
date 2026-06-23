<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? h($title) . ' — ' : '' ?>Каталог сайтов</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="bg-primary text-white py-3">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <a href="/" class="text-white text-decoration-none">
                <h1 class="h4 mb-0">Каталог сайтов</h1>
            </a>
            <a href="/add" class="btn btn-light">Добавить сайт</a>
        </div>
    </div>
</header>
