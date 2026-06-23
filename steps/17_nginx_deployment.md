# Шаг 17: Настройка nginx и фронт-контроллера

## Цель

Настроить nginx для обслуживания сайта с ЧПУ (человеко-понятными URL) и реализовать фронт-контроллер. Docker-окружение уже настроено в шаге 1.

## Конфигурация nginx

### `nginx/site.conf`

Согласно `technical.md`:

```nginx
server {
    listen 80;
    server_name homecatalog.local homecatalog.ru www.homecatalog.ru;
    root /var/www/public;
    index index.php;

    # Основной обработчик
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Раздел: /section/slug → section.php?slug=slug
    location ~ ^/section/([a-z0-9-]+)$ {
        rewrite ^/section/([a-z0-9-]+)$ /index.php?page=section&slug=$1 last;
    }

    # Сайт: /site/slug → site.php?slug=slug
    location ~ ^/site/([a-z0-9-]+)$ {
        rewrite ^/site/([a-z0-9-]+)$ /index.php?page=site&slug=$1 last;
    }

    # Статические страницы
    location /about {
        rewrite ^/about$ /index.php?page=about last;
    }

    location /rules {
        rewrite ^/rules$ /index.php?page=rules last;
    }

    location /add {
        rewrite ^/add$ /index.php?page=add last;
    }

    # Модератор
    location /moderator {
        rewrite ^/moderator$ /index.php?page=moderator/list permanent;
    }

    location ~ ^/moderator/login$ {
        rewrite ^/moderator/login$ /index.php?page=moderator/login last;
    }

    location ~ ^/moderator/list$ {
        rewrite ^/moderator/list$ /index.php?page=moderator/list last;
    }

    location ~ ^/moderator/moderate$ {
        rewrite ^/moderator/moderate$ /index.php?page=moderator/moderate last;
    }

    location ~ ^/moderator/sections$ {
        rewrite ^/moderator/sections$ /index.php?page=moderator/sections last;
    }

    location ~ ^/moderator/sections/add$ {
        rewrite ^/moderator/sections/add$ /index.php?page=moderator/sections/add last;
    }

    location ~ ^/moderator/sections/edit$ {
        rewrite ^/moderator/sections/edit$ /index.php?page=moderator/sections/edit last;
    }

    location /moderator/logout {
        rewrite ^/moderator/logout$ /index.php?page=moderator/logout last;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass php:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Защита скрытых файлов
    location ~ /\.ht {
        deny all;
    }

    # Защита конфигов и исходников
    location ~ ^/(config|src|helpers|templates|migrations|vendor)/ {
        deny all;
    }

    # Статические файлы — кэширование на 30 дней
    location ~* \.(css|js|jpg|jpeg|png|gif|ico|svg|woff2?)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

### Rewrite-правила (полный список)

| Пользовательский URL | Внутренний путь |
|---|---|
| `/` | `index.php?page=home` |
| `/section/slug` | `index.php?page=section&slug=slug` |
| `/site/slug` | `index.php?page=site&slug=slug` |
| `/about` | `index.php?page=about` |
| `/rules` | `index.php?page=rules` |
| `/add` | `index.php?page=add` |
| `/moderator/login` | `index.php?page=moderator/login` |
| `/moderator/list` | `index.php?page=moderator/list` |
| `/moderator/moderate` | `index.php?page=moderator/moderate&id=` |
| `/moderator/sections` | `index.php?page=moderator/sections` |

## Фронт-контроллер `public/index.php`

Обновлённый роутинг (вся маршрутизация через index.php):

```php
<?php
session_start();

require_once __DIR__ . '/../vendor/autoload.php';

// Загрузка конфигов
$appConfig = require __DIR__ . '/../config/app.php';
date_default_timezone_set($appConfig['timezone']);

// Подключение к БД
$db = new \App\Database(require __DIR__ . '/../config/database.php');

// Инициализация кэша
$cache = new \App\Cache(require __DIR__ . '/../config/redis.php');

// Инициализация почты
$mailer = new \App\Mailer(require __DIR__ . '/../config/mail.php');

// Роутинг
$page = $_GET['page'] ?? 'home';
$slug = $_GET['slug'] ?? null;
$id = $_GET['id'] ?? null;

// Карта маршрутов
$routes = [
    'home'                => __DIR__ . '/../pages/home.php',
    'section'             => __DIR__ . '/../pages/section.php',
    'site'                => __DIR__ . '/../pages/site.php',
    'about'               => __DIR__ . '/../pages/about.php',
    'rules'               => __DIR__ . '/../pages/rules.php',
    'add'                 => __DIR__ . '/../pages/add.php',
    'moderator/login'     => __DIR__ . '/../moderator/login.php',
    'moderator/logout'    => __DIR__ . '/../moderator/logout.php',
    'moderator/list'      => __DIR__ . '/../moderator/list.php',
    'moderator/moderate'  => __DIR__ . '/../moderator/moderate.php',
    'moderator/sections'  => __DIR__ . '/../moderator/sections.php',
    'moderator/sections/add'   => __DIR__ . '/../moderator/section_add.php',
    'moderator/sections/edit'  => __DIR__ . '/../moderator/section_edit.php',
];

if (isset($routes[$page])) {
    require $routes[$page];
} else {
    http_response_code(404);
    echo '<h1>404 — Страница не найдена</h1>';
}
```

## Docker Compose

Окружение уже настроено в **шаге 1** (`01_docker_environment.md`). Конфигурация nginx монтируется в контейнер:

```yaml
# В docker-compose.yml (шаг 1):
nginx:
  volumes:
    - ./nginx/site.conf:/etc/nginx/conf.d/default.conf:ro
```

При изменении `nginx/site.conf` — перезагрузить nginx:
```bash
docker compose exec nginx nginx -s reload
```

## Контрольные точки

- [ ] nginx принимает запросы на порт 80
- [ ] ЧПУ работают: `/section/slug`, `/site/slug`, `/about`, `/rules`, `/add`
- [ ] `/moderator/*` маршруты работают
- [ ] PHP-файлы обрабатываются через PHP-FPM (контейнер `php`)
- [ ] Статические файлы кэшируются (CSS, JS, изображения)
- [ ] Конфиги и исходники недоступны извне
- [ ] `.ht` файлы запрещены
- [ ] Контейнер `nginx` связывается с `php` через сеть `catalog_net`
