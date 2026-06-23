# Шаг 1: Настройка Docker-окружения для разработки

## Цель

Создать Docker Compose конфигурацию для запуска всего стека приложения: nginx, PHP-FPM 8.5, PostgreSQL 18, Redis.

## Стек контейнеров

| Сервис | Образ | Порт | Назначение |
|---|---|---|---|
| `nginx` | `nginx:alpine` | 80 | Веб-сервер, раздача статики, прокси на php-fpm |
| `php` | `php:8.5-fpm` | 9000 (внутр.) | PHP-FPM с нужными расширениями |
| `postgres` | `postgres:18` | 5432 | База данных |
| `redis` | `redis:7-alpine` | 6379 | Кэширование |

## Файлы для создания

### `Dockerfile` (для PHP)

Кастомный образ PHP с нужными расширениями:

```dockerfile
FROM php:8.5-fpm

# Системные зависимости
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libssl-dev \
    libzip-dev \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# PHP расширения
# openssl и mbstring уже встроены в PHP — не требуют установки
RUN docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    zip

# Redis через pecl (php-redis)
RUN pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
```

### `docker-compose.yml`

```yaml
version: '3.8'

services:
  nginx:
    image: nginx:alpine
    container_name: catalog_nginx
    ports:
      - "80:80"
    volumes:
      - ./public:/var/www/public:ro
      - ./nginx/site.conf:/etc/nginx/conf.d/default.conf:ro
    depends_on:
      - php
    networks:
      - catalog_net

  php:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: catalog_php
    volumes:
      - .:/var/www
      - ./vendor:/var/www/vendor  # исключаем vendor из bind-mount (опционально)
    environment:
      - DB_HOST=postgres
      - DB_PORT=5432
      - DB_NAME=catalog
      - DB_USER=catalog_user
      - DB_PASSWORD=FSY3hWw3NQJt
      - REDIS_HOST=redis
      - REDIS_PORT=6379
      - SMTP_HOST=smtp.example.com
      - SMTP_PORT=587
      - SMTP_USER=noreply@homecatalog.ru
      - SMTP_PASS=
      - SMTP_ENCRYPTION=tls
      - APP_DEBUG=true
    depends_on:
      - postgres
      - redis
    networks:
      - catalog_net

  postgres:
    image: postgres:18
    container_name: catalog_postgres
    environment:
      POSTGRES_DB: catalog
      POSTGRES_USER: catalog_user
      POSTGRES_PASSWORD: FSY3hWw3NQJt
    ports:
      - "5432:5432"
    volumes:
      - pgdata:/var/lib/postgresql/data
      - ./migrations:/docker-entrypoint-initdb.d  # Автоматическая миграция при старте
    networks:
      - catalog_net

  redis:
    image: redis:7-alpine
    container_name: catalog_redis
    ports:
      - "6379:6379"
    networks:
      - catalog_net

volumes:
  pgdata:

networks:
  catalog_net:
    driver: bridge
```

### `.env` (переменные окружения для docker-compose)

```env
# База данных
DB_HOST=postgres
DB_PORT=5432
DB_NAME=catalog
DB_USER=catalog_user
DB_PASSWORD=FSY3hWw3NQJt

# Redis
REDIS_HOST=redis
REDIS_PORT=6379

# SMTP
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USER=noreply@homecatalog.ru
SMTP_PASS=
SMTP_ENCRYPTION=tls

# Приложение
APP_DEBUG=true
```

### `.dockerignore`

```
.git/
documentation/
steps/
*.md
!CLAUDE.md
.env.example
promt.txt
```

## Docker Compose команды

### Запуск
```bash
# Первый запуск — сборка образа PHP
docker compose up -d --build

# Последующие запуски
docker compose up -d

# Просмотр логов
docker compose logs -f

# Остановка
docker compose down
```

### Установка Composer-зависимостей
```bash
# После первого запуска
docker compose exec php composer install

# В процессе разработки
docker compose exec php composer require phpmailer/phpmailer
docker compose exec php composer require ezyang/htmlpurifier
```

### Доступ к контейнерам
```bash
# PHP
docker compose exec php bash

# PostgreSQL
docker compose exec postgres psql -U catalog_user -d catalog

# Redis
docker compose exec redis redis-cli
```

## PHP расширения (проверка)

В `Dockerfile` устанавливаются:
- `pdo_pgsql` — драйвер PDO для PostgreSQL
- `mbstring` — работа с UTF-8
- `openssl` — SSL/TLS для SMTP
- `redis` — расширение phpredis (через pecl)

После сборки проверить:
```bash
docker compose exec php php -m | grep -E "pdo_pgsql|mbstring|openssl|redis"
```

Ожидаемый вывод:
```
mbstring
openssl
pdo_pgsql
redis
```

## Инициализация БД

Миграции в `migrations/` выполняются автоматически при первом запуске (благодаря `docker-entrypoint-initdb.d`).

Для ручного запуска SQL-файлов:
```bash
docker compose exec -T postgres psql -U catalog_user -d catalog < migrations/001_initial.sql
```

## Контрольные точки

- [ ] `docker compose up -d --build` выполняется без ошибок
- [ ] Все 4 контейнера запущены (`docker compose ps`)
- [ ] `http://localhost` открывается (nginx работает)
- [ ] PHP-FPM обрабатывает .php файлы
- [ ] PHP расширения на месте: `pdo_pgsql`, `mbstring`, `openssl`, `redis`
- [ ] Composer работает внутри контейнера
- [ ] PostgreSQL доступен: `docker compose exec postgres psql -U catalog_user -d catalog`
- [ ] Redis доступен: `docker compose exec redis redis-cli PING` → `PONG`
- [ ] Сеть `catalog_net` связывает все контейнеры
- [ ] Данные PostgreSQL сохраняются в volume `pgdata` (переживают перезапуск)
