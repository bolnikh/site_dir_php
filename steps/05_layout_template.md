# Шаг 5: Создание общего шаблона (layout)

## Цель

Реализовать общий шаблон страницы с header, breadcrumbs, main content и footer согласно макету из `pages/layout.txt`.

## Файлы

### `templates/header.php`

```php
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($title ?? 'Каталог сайтов') ?> — Каталог сайтов</title>
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
```

### `templates/footer.php`

```php
<footer class="bg-light py-3 mt-5 border-top">
    <div class="container">
        <div class="d-flex justify-content-between">
            <a href="/about" class="text-decoration-none">О нас</a>
            <a href="/rules" class="text-decoration-none">Правила</a>
        </div>
        <div class="text-center text-muted mt-2 small">
            &copy; <?= date('Y') ?> Каталог сайтов
        </div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/main.js"></script>
</body>
</html>
```

### `templates/layout.php`

Обёртка, которая принимает параметры:
- `$title` — заголовок страницы
- `$breadcrumbs` — массив хлебных крошек `[['label' => ..., 'url' => ...], ...]`
- `$content` — HTML основного блока (или через буферизацию вывода)

```php
function render_page(string $title, array $breadcrumbs, callable $content_callback): void
{
    require __DIR__ . '/header.php';
    ?>
    <!-- Flash messages -->
    <div class="container mt-3">
        <?php foreach (flash_get() as $msg): ?>
            <div class="alert alert-<?= h($msg['type']) ?> alert-dismissible fade show">
                <?= h($msg['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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
                    <li class="breadcrumb-item active"><?= h($crumb['label']) ?></li>
                <?php else: ?>
                    <li class="breadcrumb-item"><a href="<?= h($crumb['url']) ?>"><?= h($crumb['label']) ?></a></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?php endif; ?>

    <!-- Main content -->
    <main class="container mt-3">
        <?php $content_callback(); ?>
    </main>

    <?php
    require __DIR__ . '/footer.php';
}
```

### `public/assets/css/style.css`

Минимальные стили:
- Стили для списка сайтов (отступы, разделители)
- Стили для сетки разделов на главной
- Стили для breadcrumbs
- Адаптивность

## Макет согласно `layout.txt`

```
+----------------------------------------------------------------------+
|  HEADER: Каталог сайтов (слева)    Добавить сайт (справа, кнопка)    |
+----------------------------------------------------------------------+
|  BREADCRUMBS: Начало > раздел > подраздел                            |
+----------------------------------------------------------------------+
|  FLASH MESSAGES (успех / ошибка)                                      |
+----------------------------------------------------------------------+
|  MAIN CONTENT (full width, container)                                 |
+----------------------------------------------------------------------+
|  FOOTER: О нас (слева)    Правила (справа)                           |
+----------------------------------------------------------------------+
```

## Контрольные точки

- [ ] Header отображается: название слева, кнопка «Добавить сайт» справа
- [ ] Footer отображается: ссылки «О нас» и «Правила»
- [ ] Хлебные крошки рендерятся правильно (последний элемент без ссылки)
- [ ] Flash-сообщения появляются после редиректа и исчезают после обновления
- [ ] Bootstrap 5 подключён, стили применяются
- [ ] Страница адаптивна (мобильные устройства)
