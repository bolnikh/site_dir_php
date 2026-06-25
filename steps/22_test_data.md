# Тестовые данные

Задача: создать тестовые данные для сайтов.

Берем за основу разделы в миграциях. В каждый раздел сайта надо сгенерировать данные сайтов. Примерно по 15-35 сайтов в каждый раздел. Создай файл миграции.

## Реализация

### Генератор: `scripts/generate_seed_sites.php`

PHP-скрипт, который генерирует реалистичные сайты с именами, описаниями и доменами по тематикам разделов.

**Использование:**
```bash
# Запустить внутри Docker-контейнера:
docker compose exec php php scripts/generate_seed_sites.php --mode=production --per-section=25
docker compose exec -T postgres psql -U catalog_user -d catalog < migrations/004_seed_sites.sql

# Или локально (если есть PHP):
php scripts/generate_seed_sites.php --mode=production --per-section=20
```

**Параметры:**
| Параметр | Значение | По умолчанию |
|---|---|---|
| `--mode` | `production` → `migrations/004_seed_sites.sql` <br> `test` → `tests/fixtures/db-seed-gen.sql` | `production` |
| `--per-section` | Число сайтов на подраздел (15-35) | `25` |

### Что генерируется

- **Имена сайтов**: русские, тематические (30-35 уникальных шаблонов на тему)
- **Домены**: реалистичные `.ru`, `.pro`, `.shop`, `.online` и т.д.
- **Описания**: 2-4 предложения с подстановкой названия сайта
- **Email**: случайный (около 70% сайтов с email)
- **Даты**: случайные за последние 60 дней
- **Статус**: все `status=1` (опубликованы) для production-миграции

### Тематики разделов

| Тема | Разделы | Шаблонов имён | Шаблонов описаний |
|---|---|---|---|
| `home` | Дом и интерьер, Мебель, Декор, Текстиль, Освещение, Ремонт | 35 | 8 |
| `garden` | Сад и огород, Растения, Инструменты, Ландшафтный дизайн, Теплицы | 30 | 8 |
| `cooking` | Кулинария, Рецепты, Посуда, Напитки, Этикет | 30 | 8 |
| `kids` | Дети и развитие, Игрушки, Детская комната, Образование, Одежда | 30 | 8 |
| `health` | Здоровье и спорт, Фитнес, ПП, Медицина, Инвентарь | 35 | 8 |

### Выходные файлы

| Файл | Назначение | ~Сайтов |
|---|---|---|
| `migrations/004_seed_sites.sql` | Production-миграция с опубликованными сайтами | 500-800 |
| `tests/fixtures/db-seed-gen.sql` | Тестовые данные (если нужна полная перегенерация) | по потребности |

### Структура production-миграции

```sql
-- migrations/004_seed_sites.sql
BEGIN;

INSERT INTO sites (id, section_id, name, slug, url, description, email, status, created_at, moderated_at) VALUES
  (1, 6, 'МебельПро', 'mebelpro', 'https://mebelpro.ru', '...', 'info@mebelpro.ru', 1, NOW() - INTERVAL '12 days', NOW()),
  ...
ON CONFLICT (slug) DO NOTHING;

SELECT setval('sites_id_seq', GREATEST(N, (SELECT COALESCE(MAX(id), 1) FROM sites)), true);

COMMIT;
```

### Отличия от `tests/fixtures/db-seed.sql`

- `db-seed.sql` — **минимальный ручной набор** из 15 сайтов для Playwright-тестов (включая status=0 и status=2). Не перегенерируется.
- `004_seed_sites.sql` — **объёмная автогенерация** для наполнения каталога (все status=1). Перегенерируется скриптом.

### Интеграция с Docker

Миграция `004_seed_sites.sql` автоматически выполняется при первом запуске Postgres (через `/docker-entrypoint-initdb.d/`). Для тестового окружения используется `tests/fixtures/db-seed.sql`.
