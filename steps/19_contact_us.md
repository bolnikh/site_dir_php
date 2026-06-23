# Шаг 19: Страница «Свяжитесь с нами»

## Цель

Добавить публичную страницу с формой обратной связи и раздел просмотра сообщений для модератора.

## 1. Миграция БД

### Файл `migrations/003_contact_us.sql`

```sql
CREATE TABLE IF NOT EXISTS contact_us (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(64) DEFAULT '',
    message TEXT NOT NULL,
    is_read SMALLINT NOT NULL DEFAULT 0,   -- 0=новое, 1=прочитано
    created_at TIMESTAMP DEFAULT NOW()
);
```

## 2. Конфигурация

### `config/app.php` — добавить поле `admin_email`:

```php
return [
    'name' => 'Каталог сайтов',
    'url' => 'http://homecatalog.ru',
    'sites_per_page' => 20,
    'recent_sites_count' => 10,
    'debug' => filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN),
    'timezone' => 'Europe/Moscow',
    'admin_email' => 'admin@homecatalog.ru',   // ← для уведомлений
];
```

## 3. Маршруты

| URL | Файл | Описание |
|---|---|---|
| `/contact_us` | `pages/contact_us.php` | Форма обратной связи |
| `/moderator/contact_us` | `moderator/contact_us.php` | Список сообщений (модератор) |

### nginx (добавить в `site.conf`):

```nginx
location = /contact_us {
    rewrite ^ /index.php?page=contact_us last;
}

# Модераторский раздел уже обрабатывается:
# location ~ ^/moderator/ → rewrite ... /index.php?page=moderator/$1
```

### index.php — добавить роуты:

```php
'contact_us'              => __DIR__ . '/../pages/contact_us.php',
'moderator/contact_us'    => __DIR__ . '/../moderator/contact_us.php',
```

## 4. Публичная страница (`pages/contact_us.php`)

### Хлебные крошки
```
Начало > Свяжитесь с нами
```

### Форма

Поля:
| Поле | Тип | Обязательно | Валидация |
|---|---|---|---|
| `email` | email | Да | `required`, `email`, `max:255` |
| `phone` | text | Нет | `max:64` |
| `message` | textarea | Да | `required`, `string`, `max:5000` |
| `agreement` | checkbox | Да | `required`, `accepted` |

### Отображение

```html
<h2>Свяжитесь с нами</h2>

<form method="POST" action="/contact_us">
    <!-- CSRF -->
    <input type="hidden" name="csrf_token" value="...">

    <!-- Email -->
    <div class="mb-3">
        <label>Ваш Email (обязательно):</label>
        <input type="email" name="email" class="form-control" required>
    </div>

    <!-- Телефон -->
    <div class="mb-3">
        <label>Телефон (необязательно):</label>
        <input type="text" name="phone" class="form-control">
    </div>

    <!-- Сообщение -->
    <div class="mb-3">
        <label>Сообщение (обязательно):</label>
        <textarea name="message" class="form-control" rows="5" required></textarea>
    </div>

    <!-- Согласие -->
    <div class="mb-3 form-check">
        <input type="checkbox" name="agreement" class="form-check-input" required>
        <label class="form-check-label">Я согласен на обработку персональных данных</label>
    </div>

    <button type="submit" class="btn btn-primary">Отправить</button>
</form>
```

### POST-обработка

1. CSRF-проверка
2. Серверная валидация через `validate()`
3. Сохранение в БД:
   ```sql
   INSERT INTO contact_us (email, phone, message, is_read, created_at)
   VALUES (?, ?, ?, 0, NOW())
   ```
4. Отправка email администратору (`$appConfig['admin_email']`):
   - Тема: «Новое сообщение с сайта»
   - Тело: email, телефон, текст сообщения
5. Flash: «Сообщение отправлено! Мы свяжемся с вами в ближайшее время.»
6. Редирект на `/`

## 5. Модераторская страница (`moderator/contact_us.php`)

Требуется авторизация (`require_moderator()`).

### Навигация

Добавить кнопку «📬 Сообщения» в навигацию модератора (рядом с «Управление разделами»):
```html
<a href="/moderator/contact_us" class="btn btn-outline-secondary btn-sm">📬 Сообщения</a>
```

Эту кнопку нужно добавить на всех страницах модератора: `list.php`, `sections.php`, `section_add.php`, `section_edit.php`, `moderate.php`.

### Список сообщений

- Заголовок: «📬 СООБЩЕНИЯ ОТ ПОЛЬЗОВАТЕЛЕЙ»
- Хлебные крошки: `Начало > Модерация > Сообщения`
- Сортировка: самые свежие сверху (`ORDER BY created_at DESC`)
- Пагинация по 20 сообщений
- Каждое сообщение:
  - Дата и время (ДД.ММ.ГГГГ ЧЧ:ММ)
  - Email (кликабельный mailto, собирается через JS)
  - Телефон (если указан)
  - Текст сообщения
  - Статус: «новое» (красный) / «прочитано» (серый)
  - Если не прочитано — кнопка «✓ Прочитано» (POST `action=mark_read`)
  - Кнопка «🗑️ Удалить» (POST `action=delete`, с JS-подтверждением)

### Действия

**Отметить как прочитанное (`action=mark_read`):**
```sql
UPDATE contact_us SET is_read = 1 WHERE id = ?
```
Flash: «Сообщение отмечено как прочитанное.»

**Удалить (`action=delete`):**
```sql
DELETE FROM contact_us WHERE id = ?
```
Flash: «Сообщение удалено.»

После действий — редирект на `/moderator/contact_us`.

## 6. Контрольные точки

- [ ] Миграция `003_contact_us.sql` создана и применена
- [ ] `admin_email` добавлен в `config/app.php`
- [ ] `/contact_us` открывается, форма отображается
- [ ] Форма валидируется на сервере и клиенте
- [ ] Сообщение сохраняется в БД
- [ ] Email-уведомление отправляется администратору
- [ ] `/moderator/contact_us` доступен только модератору
- [ ] Список сообщений с пагинацией
- [ ] Кнопка «Прочитано» работает
- [ ] Кнопка «Удалить» работает
- [ ] Ссылка на раздел есть в навигации модератора
- [ ] Flash-сообщения после действий

## Зависимости

- Шаг 10 (форма добавления сайта) — аналогичная логика валидации
- Шаг 11 (авторизация модератора) — `require_moderator()`
- Шаг 16 (email-уведомления) — PHPMailer
