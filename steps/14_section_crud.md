# Шаг 14: CRUD управление разделами (модератор)

## Цель

Реализовать страницы управления разделами: просмотр дерева, добавление, редактирование и удаление разделов.

## Маршруты

| URL | Файл | Описание |
|---|---|---|
| `/moderator/sections` | `moderator/sections.php` | Дерево разделов с фильтром |
| `/moderator/sections/add` | `moderator/section_add.php` | Добавление раздела |
| `/moderator/sections/edit?id={id}` | `moderator/section_edit.php` | Редактирование раздела |
| `/moderator/sections/delete` | (POST) в `sections.php` | Удаление раздела |

Все требуют авторизации модератора.

---

## 13.1 Дерево разделов (`moderator/sections.php`)

### Отображение

Согласно `sections_tree.txt`:

- Заголовок: «📂 УПРАВЛЕНИЕ РАЗДЕЛАМИ»
- Фильтр по названию: текстовое поле + кнопка «Найти» + кнопка «Сбросить»
- Дерево разделов с отступами:
  ```
  ┌─ Дом и интерьер (12 сайтов) ───── [✏️ ред.] [➕ подраздел] [🗑️ удал.]
  │  ├─ Мебель (3) ─────── [✏️ ред.] [➕ подраздел] [🗑️ удал.]
  │  ├─ Декор (2) ──────── [✏️ ред.] [➕ подраздел] [🗑️ удал.]
  ...
  ```

### Запросы

**Все разделы (для дерева):**
```sql
SELECT s.*,
       (SELECT COUNT(*) FROM sites WHERE section_id = s.id) as site_count,
       (SELECT COUNT(*) FROM sites WHERE section_id IN (
           SELECT id FROM sections WHERE path LIKE s.path || '/%' OR id = s.id
       )) as total_site_count
FROM sections s
ORDER BY path
```

**С фильтром по названию:**
```sql
-- Показать только разделы, чьё имя содержит поисковую строку,
-- вместе с их полным путём от корня
SELECT DISTINCT s.*, ...
FROM sections s
WHERE s.name ILIKE '%поиск%'
   OR s.id IN (
       SELECT parent_id FROM sections WHERE name ILIKE '%поиск%'
   )
   OR s.id IN (
       SELECT id FROM sections WHERE path LIKE (
           SELECT CONCAT(path, '%') FROM sections WHERE name ILIKE '%поиск%' LIMIT 1
       )
   )
ORDER BY s.path
```

### Подсчёт сайтов

Для каждого раздела показываем количество сайтов **непосредственно в нём** (для проверки возможности удаления). Число в скобках — сайты только этого раздела (не включая дочерние).

### Кнопки действий

- **✏️ ред.** → `/moderator/sections/edit?id={id}`
- **➕ подраздел** → `/moderator/sections/add?parent_id={id}`
- **🗑️ удал.** → форма POST на `/moderator/sections/delete` (только если `site_count = 0`)

### Удаление раздела (POST)

```sql
DELETE FROM sections WHERE id = :id
  AND (SELECT COUNT(*) FROM sites WHERE section_id = :id) = 0
  AND (SELECT COUNT(*) FROM sections WHERE parent_id = :id) = 0
```

Проверки:
1. В разделе нет сайтов
2. В разделе нет дочерних разделов (или переносим их?)

Если есть сайты → flash error: «Нельзя удалить раздел: в нём есть сайты.»

После удаления → инвалидация кэша разделов → редирект на `/moderator/sections`.

---

## 13.2 Добавление раздела (`moderator/section_add.php`)

### Отображение

Согласно `section_add.txt`:

- Хлебные крошки: `Начало > Управление разделами > Добавить раздел`
- Поля:
  1. Родительский раздел (select, опционально — пусто = корневой)
  2. Название раздела (text, обязательно)
  3. Slug (text, автогенерация из названия, можно редактировать)
  4. Описание (Summernote)
- Кнопка «💾 СОХРАНИТЬ РАЗДЕЛ»

### POST-обработка

1. Валидация: `name` обязательно, `slug` уникален
2. Вычисление `path`:
   - Если есть `parent_id`: `path = parent.path + '/' + new_id`
   - Если корневой: `path = id`
   - (Сначала INSERT, потом UPDATE path с новым id)
3. INSERT:
   ```sql
   INSERT INTO sections (parent_id, path, name, slug, description) 
   VALUES (:parent_id, '', :name, :slug, :description)
   RETURNING id
   ```
4. Обновить path:
   ```sql
   UPDATE sections SET path = CASE 
       WHEN parent_id IS NULL THEN id::text
       ELSE (SELECT path FROM sections WHERE id = parent_id) || '/' || id::text
   END
   WHERE id = :id
   ```
5. Инвалидация кэша: `sections:tree`, `sections:children:*`
6. Flash success, редирект на `/moderator/sections`

---

## 13.3 Редактирование раздела (`moderator/section_edit.php`)

### Отображение

Согласно `section_edit.txt`:

- Хлебные крошки: `Начало > Управление разделами > Название раздела`
- Текущий путь: `Дом и интерьер / Мебель`
- Поля (как при добавлении, но предзаполненные):
  1. Родительский раздел (select)
  2. Название (text)
  3. Slug (text)
  4. Описание (Summernote)
- Кнопка «💾 СОХРАНИТЬ ИЗМЕНЕНИЯ»
- Блок «Опасная зона»:
  - Информация: «Раздел содержит: N подразделов, M сайтов»
  - Кнопка «🗑️ УДАЛИТЬ РАЗДЕЛ» (неактивна если есть сайты)

### POST-обработка

1. Валидация
2. Если изменился `parent_id` — пересчитать `path` для этого раздела и всех его потомков
3. UPDATE:
   ```sql
   UPDATE sections SET parent_id = :parent_id, name = :name, 
          slug = :slug, description = :description
   WHERE id = :id
   ```
4. Если изменился parent — обновить path рекурсивно для всего поддерева
5. Инвалидация кэша
6. Flash success, редирект

### Пересчёт path при смене родителя

```sql
-- Для корневого раздела:
UPDATE sections SET path = id::text WHERE id = :id;

-- Для дочернего:
UPDATE sections SET path = (SELECT path FROM sections WHERE id = :parent_id) || '/' || id::text
WHERE id = :id;

-- Для всех потомков (рекурсивно обновить префикс пути)
-- Проще всего: перестроить path для всего поддерева через рекурсивную функцию PHP
-- или использовать запрос с REPLACE старого префикса на новый
```

---

## Контрольные точки

- [ ] Дерево разделов отображается с правильными отступами
- [ ] Количество сайтов отображается для каждого раздела
- [ ] Фильтр по названию работает
- [ ] Кнопка «Добавить подраздел» создаёт дочерний раздел
- [ ] Кнопка «Редактировать» открывает форму редактирования
- [ ] Кнопка «Удалить» удаляет только пустые разделы
- [ ] Нельзя удалить раздел с сайтами (кнопка неактивна или ошибка)
- [ ] Slug генерируется автоматически, но можно изменить
- [ ] При добавлении path вычисляется корректно
- [ ] При смене родителя path пересчитывается для всего поддерева
- [ ] Кэш инвалидируется при любом изменении разделов
- [ ] Все страницы защищены авторизацией модератора
