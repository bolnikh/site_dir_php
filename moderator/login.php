<?php
/**
 * Страница входа модератора — /moderator/login
 */

// Если уже авторизован — редирект на список
if (!empty($_SESSION['moderator_id'])) {
    header('Location: /moderator/list');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Введите логин и пароль.';
    } else {
        // Найти пользователя
        $user = $db->fetch(
            'SELECT * FROM users WHERE username = ? AND active = 1',
            [$username]
        );

        if ($user && password_verify($password, $user['password'])) {
            // Успешный вход
            $_SESSION['moderator_id'] = (int) $user['id'];
            $_SESSION['moderator_username'] = $user['username'];

            header('Location: /moderator/list');
            exit;
        } else {
            $error = 'Неверный логин или пароль.';
        }
    }
}

render_page('Вход для модератора', breadcrumbs_static('Вход'), function () use ($error) {
    ?>
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <h2 class="text-center mb-4">Логин</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= h($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="/moderator/login">
                <div class="mb-3">
                    <label for="username" class="form-label">Логин:</label>
                    <input type="text" name="username" id="username"
                           class="form-control" required autofocus
                           value="<?= h($_POST['username'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Пароль:</label>
                    <input type="password" name="password" id="password"
                           class="form-control" required>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" name="remember" id="remember" class="form-check-input">
                    <label for="remember" class="form-check-label">Запомнить меня</label>
                </div>

                <button type="submit" class="btn btn-primary w-100">Войти</button>
            </form>
        </div>
    </div>
    <?php
});
