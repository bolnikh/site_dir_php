# Шаг 7: Страница раздела / подраздела

## Цель

Реализовать страницу раздела (`/section/{slug}` → `section.php`), отображающую описание раздела, подразделы и постраничный список сайтов.

## Маршрут

`/section/{slug}` → `pages/section.php?slug=`

## Алгоритм

1. Получить `slug` из URL
2. Найти раздел по slug в БД
3. Если не найден → 404
4. Построить хлебные крошки (используя `path` для обхода родителей)
5. Получить список подразделов (если есть)
6. Получить количество сайтов в разделе (включая сайты всех дочерних разделов)
7. Получить список сайтов с пагинацией (текущая страница из `$_GET['page']`, по умолчанию 1)

## Запросы

### Раздел по slug
```sql
SELECT * FROM sections WHERE slug = :slug
```

### Хлебные крошки (по path)

Path имеет формат `"1/6/15"`. Разбиваем, получаем id каждого родителя:
```sql
SELECT id, name, slug, parent_id FROM sections WHERE id IN (1, 6, 15) ORDER BY path
```

Или через рекурсивный CTE:
```sql
WITH RECURSIVE parents AS (
    SELECT id, parent_id, name, slug, 0 as depth
    FROM sections WHERE id = :id
    UNION ALL
    SELECT s.id, s.parent_id, s.name, s.slug, p.depth + 1
    FROM sections s JOIN parents p ON s.id = p.parent_id
)
SELECT * FROM parents ORDER BY depth DESC
```

### Подразделы
```sql
SELECT * FROM sections WHERE parent_id = :section_id ORDER BY name
```
Кэш: `sections:children:{section_id}` (TTL: 1 час)

### Количество сайтов (в разделе и всех его подразделах)
```sql
-- Все id разделов, включая дочерние (через path LIKE)
SELECT COUNT(*) FROM sites
WHERE section_id IN (
    SELECT id FROM sections WHERE path LIKE :path_prefix || '%'
) AND status = 1
```

Где `path_prefix` = `path` текущего раздела (например, `'1/6'` — подпадает и `1/6`, и `1/6/15`).

### Сайты раздела (с пагинацией)
```sql
SELECT st.*, s.name as section_name, s.slug as section_slug
FROM sites st
JOIN sections s ON st.section_id = s.id
WHERE st.section_id IN (
    SELECT id FROM sections WHERE path LIKE :path_prefix || '%'
) AND st.status = 1
ORDER BY st.created_at DESC
LIMIT :limit OFFSET :offset
```
Кэш: `sites:section:{section_id}:page:{N}` (TTL: 5 минут)

## Отображение

Согласно `subrazdels.txt` и `subrazdel_site_list.txt`:

### Заголовок и описание
- H1: Название раздела
- P: Описание раздела (из БД)

### Блок подразделов
Если есть подразделы — сетка с названиями-ссылками:
```
[ РАСТЕНИЯ ]    [ ИНСТРУМЕНТЫ ]    [ ЛАНДШАФТНЫЙ ДИЗАЙН ]
[ ТЕПЛИЦЫ И ПАРНИКИ ]
```

### Список сайтов
- Заголовок: «Сайты в разделе: N» (где N — общее количество)
- Нумерованный список
- Каждый сайт: название (ссылка), URL (мелко), дата добавления (мелко)

### Пагинация
- Внизу: [1] [2] [3] ... ← Страница N из M (всего X сайтов)
- Текущая страница выделена
- `?page=N` в URL

### Хлебные крошки
```
Начало > Дом и интерьер > Мебель
(ссылка)  (ссылка, если не текущий)  (текущий, без ссылки)
```

## Контрольные точки

- [ ] Страница открывается по `/section/{slug}`
- [ ] Несуществующий slug → 404 или редирект на главную
- [ ] Хлебные крошки полные для любой глубины
- [ ] Описание раздела отображается
- [ ] Подразделы отображаются (если есть)
- [ ] Список сайтов включает сайты из всех дочерних разделов
- [ ] Пагинация работает (по 20 сайтов на страницу, настраивается в конфиге)
- [ ] Кэширование работает (Redis)
- [ ] Пустой раздел (нет сайтов) — выводится сообщение об этом
