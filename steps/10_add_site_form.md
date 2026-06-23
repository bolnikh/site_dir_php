# Шаг 10: Форма добавления сайта

## Цель

Реализовать страницу `/add` с формой для отправки сайта на модерацию.

## Маршрут

`/add` → `pages/add.php`

## Алгоритм

### GET-запрос
1. Загрузить список разделов для селекта (дерево с отступами)
2. Отобразить форму

### POST-запрос
1. Получить данные формы
2. Провести серверную валидацию (согласно `technical.md`)
3. Если ошибки → показать форму с ошибками
4. Если успешно → сохранить сайт в БД, flash-сообщение, редирект на главную

## Поля формы (согласно `add_site.txt`)

| Поле | Тип | Обязательно | Валидация |
|---|---|---|---|
| `section_id` | select | Да | `required`, `integer`, `exists:sections,id` |
| `name` | text | Да | `required`, `string`, `max:512` |
| `url` | text | Да | `required`, `url`, `max:512`, `unique:sites,url` |
| `description` | textarea (Summernote) | Да | `required`, `string`, `max:10000` |
| `email` | email | Нет | `nullable`, `email`, `max:255` |
| `agreement` | checkbox | Да | `required`, `accepted` |

## Отображение

Согласно `add_site.txt`:

### Хлебные крошки
```
Начало > Добавить сайт
```

### Форма

```html
<h1>Добавить сайт в каталог</h1>

<form method="POST" action="/add" id="add-site-form">
    <!-- 1. Раздел сайта -->
    <div class="mb-3">
        <label for="section_id" class="form-label">1. Раздел сайта (обязательно):</label>
        <select name="section_id" id="section_id" class="form-select" required>
            <option value="">-- Выберите раздел --</option>
            <!-- Дерево разделов с отступами -->
        </select>
        <div class="invalid-feedback">Выберите раздел.</div>
    </div>

    <!-- 2. Название сайта -->
    <div class="mb-3">
        <label for="name" class="form-label">2. Название сайта (обязательно):</label>
        <input type="text" name="name" id="name" class="form-control" maxlength="512" required>
    </div>

    <!-- 3. URL сайта -->
    <div class="mb-3">
        <label for="url" class="form-label">URL сайта (обязательно):</label>
        <input type="url" name="url" id="url" class="form-control" maxlength="512" placeholder="https://" required>
    </div>

    <!-- 4. Описание сайта (Summernote) -->
    <div class="mb-3">
        <label for="description" class="form-label">3. Описание сайта (обязательно):</label>
        <textarea name="description" id="description" class="form-control summernote" maxlength="10000" required></textarea>
    </div>

    <!-- 5. Email -->
    <div class="mb-3">
        <label for="email" class="form-label">4. Email (для связи, необязательно):</label>
        <input type="email" name="email" id="email" class="form-control" maxlength="255">
    </div>

    <!-- 6. Согласие с правилами -->
    <div class="mb-3 form-check">
        <input type="checkbox" name="agreement" id="agreement" class="form-check-input" required>
        <label for="agreement" class="form-check-label">
            5. Я принимаю условия и согласен с правилами каталога
        </label>
    </div>

    <!-- 7. Кнопка отправки -->
    <button type="submit" class="btn btn-primary">Отправить на модерацию</button>
</form>
```

## Валидация (JavaScript)

Клиентская валидация ДО отправки:
- Проверка заполненности обязательных полей
- Проверка формата URL
- Проверка формата email (если заполнен)
- Проверка чекбокса согласия
- Вывод ошибок рядом с полями (Bootstrap `is-invalid` + `.invalid-feedback`)

## Сохранение в БД

```sql
INSERT INTO sites (section_id, name, slug, url, description, email, status, created_at)
VALUES (:section_id, :name, :slug, :url, :description, :email, 0, NOW())
```

- `slug` генерируется из `name` с проверкой уникальности (если занят — добавляем суффикс `-2`, `-3`...)
- `description` очищается через `clean_html()` (HTMLPurifier)
- `status = 0` (на модерации)

## Flash-сообщение

После успешного сохранения:
```php
flash_set('success', 'Сайт успешно отправлен на модерацию! Он появится в каталоге после проверки.');
header('Location: /');
exit;
```

## Контрольные точки

- [ ] Форма открывается по `/add`
- [ ] Селект разделов показывает дерево с отступами (`—` или `├─`)
- [ ] Summernote (визуальный редактор) работает для поля описания
- [ ] JS-валидация проверяет поля до отправки
- [ ] Серверная валидация проверяет все правила
- [ ] При ошибках форма перепоказывается с сообщениями об ошибках
- [ ] Успешная отправка → flash-сообщение → редирект на `/`
- [ ] Slug генерируется автоматически из названия
- [ ] Уникальность URL проверяется (нельзя добавить один URL дважды)
- [ ] XSS-защита: описание фильтруется через HTMLPurifier
- [ ] Хлебные крошки: «Начало > Добавить сайт»
