# Технические детали

## SMTP (отправка почты)

### Конфигурация

В `config/mail.php`:

```php
return [
    'host'     => 'smtp.example.com',
    'port'     => 587,
    'username' => 'noreply@homecatalog.ru',
    'password' => '***',
    'encryption' => 'tls',  // tls или ssl
    'from'     => 'noreply@homecatalog.ru',
    'from_name' => 'Каталог сайтов',
];
```

### Шаблоны писем

**Письмо о принятии (templates/emails/site_approved.php):**

```
Тема: Ваш сайт "{site_name}" опубликован!

Здравствуйте!

Ваш сайт "{site_name}" прошёл модерацию и опубликован в каталоге.
Посмотреть: https://homecatalog.ru/site/{id}

С уважением, Каталог сайтов
```

**Письмо об отклонении (templates/emails/site_rejected.php):**

```
Тема: Ваш сайт "{site_name}" отклонён

Здравствуйте!

К сожалению, ваш сайт "{site_name}" не прошёл модерацию.
Если вы считаете это ошибкой, свяжитесь с нами: catalog@homecatalog.ru

С уважением, Каталог сайтов
```

**Отправка:** использовать `mail()` для отладки, затем PHPMailer или SwiftMailer для продакшена.

---

## Redis (кэш)

### Что кэшируем

| Данные | Ключ | TTL | Инвалидация |
|---|---|---|---|
| Дерево разделов (главная) | `sections:tree` | 1 час | При изменении разделов |
| Последние 10 сайтов | `sites:recent:10` | 5 минут | При публикации нового сайта |
| Сайты раздела (страница N) | `sites:section:{section_id}:page:{N}` | 5 минут | При публикации/удалении в разделе |
| Страница сайта (по slug/id) | `site:{slug_or_id}` | 30 минут | При редактировании/удалении |
| Подразделы раздела | `sections:children:{parent_id}` | 1 час | При изменении разделов |

### Подключение

В `config/redis.php`:
```php
return [
    'host' => 'redis',
    'port' => 6379,
    'prefix' => 'catalog:',
];
```

Использовать `predis/predis` (composer) или расширение `phpredis` как драйвер.

---

## Визуальный редактор

**Рекомендация:** TinyMCE (бесплатная версия, без API-ключа) или Summernote (Bootstrap-совместимый, легче).

Базовый набор кнопок (как в макетах):
- Bold, Italic, Underline
- Bullet list, Ordered list
- Quote, Link

**Подключение TinyMCE (через CDN):**
```html
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js"></script>
<script>
tinymce.init({
  selector: 'textarea.editor',
  plugins: 'lists link',
  toolbar: 'bold italic underline | bullist numlist | blockquote link',
  menubar: false,
  height: 200
});
</script>
```

**Альтернатива (Summernote, Bootstrap-совместимый):**
```html
<script src="https://cdn.jsdelivr.net/npm/summernote/dist/summernote-bs4.min.js"></script>
```

---

## nginx конфигурация

Пример `nginx/site.conf`:

```nginx
server {
    listen 80;
    server_name homecatalog.local;
    root /var/www/public;
    index index.php;

    # ЧПУ: /section/slug → section.php?slug=slug
    #      /site/123     → site.php?id=123
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ ^/section/([a-z0-9-]+)$ {
        rewrite ^/section/([a-z0-9-]+)$ /section.php?slug=$1 last;
    }

    location ~ ^/site/([0-9]+)$ {
        rewrite ^/site/([0-9]+)$ /site.php?id=$1 last;
    }

    location ~ \.php$ {
        fastcgi_pass php-fpm:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

### Rewrite-правила (полный список)

| Пользовательский URL | Внутренний путь |
|---|---|
| `/` | `index.php` |
| `/section/slug` | `section.php?slug=slug` |
| `/site/123` | `site.php?id=123` |
| `/about` | `about.php` |
| `/rules` | `rules.php` |
| `/add` | `add.php` |
| `/admin` | `admin/index.php` |
| `/admin/login` | `admin/login.php` |
| `/admin/moderate/123` | `admin/moderate.php?id=123` |
| `/admin/sections` | `admin/sections.php` |
| `/admin/sections/add` | `admin/section_add.php` |
| `/admin/sections/edit/5` | `admin/section_edit.php?id=5` |

---

## Flash-сообщения (через сессию)

### Типы сообщений

| Тип | Класс Bootstrap | Пример |
|---|---|---|
| `success` | `alert-success` | «Сайт успешно отправлен на модерацию!» |
| `error` | `alert-danger` | «Ошибка: заполните обязательные поля.» |
| `warning` | `alert-warning` | «Раздел не выбран — будет назначен по умолчанию.» |
| `info` | `alert-info` | «Сайт находится на модерации.» |

### Реализация

`helpers/flash.php`:
- `flash_set(string $type, string $message)` — сохраняет сообщение в `$_SESSION['flash']`
- `flash_get()` — возвращает и очищает все сообщения

### Вывод в layout

```php
<?php foreach (flash_get() as $msg): ?>
  <div class="alert alert-<?= $msg['type'] ?> alert-dismissible fade show">
    <?= htmlspecialchars($msg['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endforeach; ?>
```

---

## Валидация (серверная)

### Правила для формы добавления сайта

| Поле | Правила |
|---|---|
| `section_id` | `required`, `integer`, `exists:sections,id` |
| `name` | `required`, `string`, `max:512` |
| `url` | `required`, `url`, `max:512`, `unique:sites,url` |
| `description` | `required`, `string`, `max:10000` |
| `email` | `nullable`, `email`, `max:255` |
| `agreement` | `required`, `accepted` |

### XSS-защита

- Всегда использовать `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')` при выводе пользовательских данных
- Для описания сайта (HTML из редактора): использовать HTMLPurifier для фильтрации тегов
- Никогда не выводить неэкранированные данные в HTML
