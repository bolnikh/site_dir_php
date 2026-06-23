# Шаг 2: Создание структуры проекта и конфигурации

## Цель

Создать базовую файловую структуру проекта, конфигурационные файлы и точку входа.

## Файлы для создания

### Структура директорий

```
/
├── public/                  # Document root для nginx
│   ├── index.php            # Точка входа (фронт-контроллер)
│   └── assets/              # Статические файлы
│       ├── css/
│       │   └── style.css    # Пользовательские стили
│       └── js/
│           └── main.js      # Пользовательский JS
├── config/
│   ├── app.php              # Основные настройки приложения
│   ├── database.php         # Подключение к PostgreSQL
│   ├── redis.php            # Подключение к Redis
│   └── mail.php             # Настройки SMTP
├── src/
│   ├── Database.php         # Класс для PDO-соединения
│   ├── Cache.php            # Класс для работы с Redis
│   └── Mailer.php           # Класс для отправки почты
├── helpers/
│   ├── flash.php            # Flash-сообщения через сессию
│   ├── validation.php       # Серверная валидация
│   ├── breadcrumbs.php      # Формирование хлебных крошек
│   ├── pagination.php       # Постраничная навигация
│   └── security.php         # XSS-защита (htmlspecialchars wrapper)
├── templates/
│   ├── layout.php           # Общий шаблон страницы
│   ├── header.php           # Верхняя часть (header)
│   ├── footer.php           # Нижняя часть (footer)
│   └── emails/
│       ├── site_approved.php
│       └── site_rejected.php
├── pages/                   # Страницы сайта
│   ├── home.php             # Главная (/)
│   ├── section.php          # Раздел/подраздел (/section/slug)
│   ├── site.php             # Страница сайта (/site/slug)
│   ├── about.php            # О нас (/about)
│   ├── rules.php            # Правила (/rules)
│   └── add.php              # Добавить сайт (/add)
├── moderator/               # Страницы модератора
│   ├── login.php            # Логин модератора
│   ├── list.php             # Список сайтов на модерацию
│   ├── moderate.php         # Модерация отдельного сайта
│   ├── sections.php         # Управление разделами (дерево)
│   ├── section_add.php      # Добавление раздела
│   └── section_edit.php     # Редактирование раздела
├── migrations/
│   └── 001_initial.sql      # Начальная миграция БД
├── nginx/
│   └── site.conf            # Конфигурация nginx
├── composer.json            # Зависимости
└── vendor/                  # Автозагрузка (после composer install)
```

### Конфигурационные файлы

#### `config/app.php`
```php
return [
    'name' => 'Каталог сайтов',
    'url' => 'http://homecatalog.ru',
    'sites_per_page' => 20,
    'recent_sites_count' => 10,
    'debug' => false,
    'timezone' => 'Europe/Moscow',
];
```

#### `config/database.php`

Используются креды из `technical.md`:

```php
return [
    'driver' => 'pgsql',
    'host' => getenv('DB_HOST') ?: 'postgres',
    'port' => getenv('DB_PORT') ?: 5432,
    'database' => getenv('DB_NAME') ?: 'catalog',
    'username' => getenv('DB_USER') ?: 'catalog_user',
    'password' => getenv('DB_PASSWORD') ?: 'FSY3hWw3NQJt',
    'charset' => 'utf8',
];
```

#### `config/redis.php` — согласно technical.md
#### `config/mail.php` — согласно technical.md

### Точка входа `public/index.php`

```php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

// Загрузка конфигов
$config = require __DIR__ . '/../config/app.php';
date_default_timezone_set($config['timezone']);

// Подключение к БД
$db = new \App\Database(require __DIR__ . '/../config/database.php');

// Инициализация кэша
$cache = new \App\Cache(require __DIR__ . '/../config/redis.php');

// Роутинг: разбор $_SERVER['REQUEST_URI'] → подключение нужной страницы
// (простая реализация без отдельного роутера)
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// ... routing logic
```

### `composer.json`
```json
{
    "name": "homecatalog/site-directory",
    "description": "Каталог сайтов",
    "require": {
        "php": ">=8.5",
        "phpmailer/phpmailer": "^6.8",
        "ezyang/htmlpurifier": "^4.16"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        },
        "files": [
            "helpers/flash.php",
            "helpers/validation.php",
            "helpers/breadcrumbs.php",
            "helpers/pagination.php",
            "helpers/security.php"
        ]
    }
}
```

## Контрольные точки

- [ ] Структура директорий создана
- [ ] Все конфигурационные файлы на месте
- [ ] composer.json готов, `docker compose exec php composer install` выполнен успешно
- [ ] `public/index.php` принимает запросы
- [ ] Подключение к БД работает (проверка через `docker compose exec php php -r "..."`)
- [ ] Автозагрузка классов работает
- [ ] Конфиги читают переменные окружения из Docker Compose
