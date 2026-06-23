# Шаг 11: Авторизация модератора

## Цель

Реализовать страницу входа модератора и механизм сессионной аутентификации.

## Маршруты

- `/moderator/login` → `moderator/login.php`
- `/moderator/*` — все остальные страницы модератора требуют авторизации

## Страница логина (`moderator/login.php`)

### GET — показ формы

Согласно `moderator_login.txt`:

```html
<h1>Логин</h1>

<form method="POST" action="/moderator/login">
    <div class="mb-3">
        <label for="username" class="form-label">Логин:</label>
        <input type="text" name="username" id="username" class="form-control" required autofocus>
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">Пароль:</label>
        <input type="password" name="password" id="password" class="form-control" required>
    </div>

    <div class="mb-3 form-check">
        <input type="checkbox" name="remember" id="remember" class="form-check-input">
        <label for="remember" class="form-check-label">Запомнить меня</label>
    </div>

    <button type="submit" class="btn btn-primary">Войти</button>
</form>
```

### POST — проверка логина/пароля

1. Получить `username` и `password` из POST
2. Найти пользователя:
   ```sql
   SELECT * FROM users WHERE username = :username AND active = 1
   ```
3. Проверить пароль через `password_verify($password, $user['password'])`
4. При успехе:
   - `$_SESSION['moderator_id'] = $user['id']`
   - `$_SESSION['moderator_username'] = $user['username']`
   - Если «Запомнить» — установить cookie с токеном (опционально)
   - Редирект на `/moderator/list`
5. При ошибке — flash-сообщение об ошибке, показать форму снова

## Middleware авторизации

Функция `require_moderator(): void`:
- Проверяет `$_SESSION['moderator_id']`
- Если нет — редирект на `/moderator/login`
- Вызывается в начале каждой страницы модератора

Файл: `helpers/auth.php` (или в `moderator/_middleware.php`)

## Выход

`/moderator/logout`:
```php
unset($_SESSION['moderator_id']);
unset($_SESSION['moderator_username']);
session_destroy();
header('Location: /moderator/login');
exit;
```

## Генерация хеша пароля

Для начального пользователя (из миграции):
```php
$hash = password_hash('admin123', PASSWORD_BCRYPT);
echo $hash;
```

## Контрольные точки

- [ ] `/moderator/login` показывает форму входа
- [ ] Правильный логин/пароль — вход и редирект на список
- [ ] Неправильный логин/пароль — ошибка, форма перепоказывается
- [ ] Страницы `/moderator/*` недоступны без авторизации (редирект на логин)
- [ ] После входа `$_SESSION` содержит id и username модератора
- [ ] Выход (`/moderator/logout`) очищает сессию
- [ ] Пароль хранится в БД как bcrypt-хеш
- [ ] Форма не содержит XSS-уязвимостей
