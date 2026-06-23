# Шаги реализации проекта «Каталог сайтов»

## Обзор

Проект: каталог сайтов с публичной частью и панелью модератора.
Стек: PHP 8.5 (php-fpm) + PostgreSQL 18 + Redis + Bootstrap 5 + nginx + Docker Compose.

## Список шагов

| # | Файл | Описание | Зависит от |
|---|---|---|---|
| 01 | [01_docker_environment.md](01_docker_environment.md) | Docker Compose: nginx, PHP-FPM 8.5, PostgreSQL 18, Redis | — |
| 02 | [02_project_structure.md](02_project_structure.md) | Структура проекта, composer, конфиги | 01 |
| 03 | [03_database_migration.md](03_database_migration.md) | Таблицы PostgreSQL, начальные данные | 01, 02 |
| 04 | [04_core_helpers.md](04_core_helpers.md) | Базовые классы и хелперы (DB, Cache с phpredis, Mailer с PHPMailer) | 02, 03 |
| 05 | [05_layout_template.md](05_layout_template.md) | Общий шаблон страницы (header/footer/breadcrumbs) | 04 |
| 06 | [06_home_page.md](06_home_page.md) | Главная страница с разделами и последними сайтами | 05 |
| 07 | [07_section_page.md](07_section_page.md) | Страница раздела с подразделами и списком сайтов | 05, 06 |
| 08 | [08_site_page.md](08_site_page.md) | Страница отдельного сайта | 05 |
| 09 | [09_static_pages.md](09_static_pages.md) | Страницы «О нас» и «Правила» | 05 |
| 10 | [10_add_site_form.md](10_add_site_form.md) | Форма добавления сайта пользователем | 05, 06 |
| 11 | [11_moderator_auth.md](11_moderator_auth.md) | Авторизация модератора | 05 |
| 12 | [12_moderator_site_list.md](12_moderator_site_list.md) | Список сайтов на модерации | 11 |
| 13 | [13_moderator_moderate_site.md](13_moderator_moderate_site.md) | Модерация (одобрить/отклонить/редактировать) | 12 |
| 14 | [14_section_crud.md](14_section_crud.md) | CRUD управления разделами | 11 |
| 15 | [15_redis_caching.md](15_redis_caching.md) | Интеграция Redis-кэширования (phpredis) | 06, 07, 08 |
| 16 | [16_email_notifications.md](16_email_notifications.md) | Email-уведомления через PHPMailer | 13 |
| 17 | [17_nginx_deployment.md](17_nginx_deployment.md) | Настройка nginx и фронт-контроллера | 01, все |
| 18 | [18_testing_and_final.md](18_testing_and_final.md) | Финальное тестирование и проверка | все |

## Порядок выполнения

```
01_docker_environment    ─────── Docker-окружение
                            │
02_project_structure     ──┐
03_database_migration   ──┤
04_core_helpers         ──┤── базовая инфраструктура
05_layout_template      ──┘
                            │
06_home_page            ──┐
07_section_page         ──┤
08_site_page            ──┤── публичные страницы
09_static_pages         ──┤
10_add_site_form        ──┘
                            │
11_moderator_auth       ──┐
12_moderator_site_list  ──┤
13_moderator_moderate_site ─┤── модераторская часть
14_section_crud         ──┘
                            │
15_redis_caching        ──┐── инфраструктурные улучшения
16_email_notifications  ──┤
                            │
17_nginx_deployment     ──┘── nginx
18_testing_and_final    ─────── финальная проверка
```

## Структура проекта (после реализации)

```
/
├── Dockerfile                # Кастомный PHP 8.5 образ
├── docker-compose.yml        # Docker Compose конфигурация
├── .env                      # Переменные окружения
├── .dockerignore
├── public/                   # Document root
│   ├── index.php             # Фронт-контроллер
│   └── assets/
├── config/                   # Конфигурационные файлы
├── src/                      # Классы (Database, Cache, Mailer)
├── helpers/                  # Вспомогательные функции
├── templates/               # Шаблоны
│   └── emails/
├── pages/                    # Публичные страницы
├── moderator/                # Страницы модератора
├── migrations/               # SQL-миграции
├── nginx/                    # Конфигурация nginx
│   └── site.conf
├── documentation/            # Документация проекта
├── steps/                    # Шаги реализации (эта директория)
├── composer.json
└── vendor/
```

## Соглашения

- PHP 8.5, строгая типизация
- PDO PostgreSQL с prepared statements (безопасность)
- Redis через расширение `phpredis` (pecl)
- Все пользовательские данные экранируются через `h()` (htmlspecialchars)
- HTML из редактора фильтруется через HTMLPurifier
- Кэш через Redis с graceful degradation
- Почта через PHPMailer + SMTP
- Flash-сообщения через сессию
- Bootstrap 5 для стилей
- Запуск через Docker Compose
