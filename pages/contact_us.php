<?php
/**
 * Страница «Свяжитесь с нами» — /contact_us
 */

$errors = [];
$old = ['email' => '', 'phone' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        flash_set('error', 'Ошибка безопасности. Пожалуйста, попробуйте снова.');
        header('Location: /contact_us');
        exit;
    }

    $old = [
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'message' => trim($_POST['message'] ?? ''),
    ];

    $errors = validate($_POST, [
        'email' => 'required|email|max:255',
        'phone' => 'nullable|max:64',
        'message' => 'required|string|max:5000',
        'agreement' => 'required|accepted',
    ], $db);

    if (validation_passed($errors)) {
        // Сохранение в БД
        $db->insert(
            'INSERT INTO contact_us (email, phone, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())',
            [$old['email'], $old['phone'], $old['message']]
        );

        // Отправка email администратору
        $adminEmail = $appConfig['admin_email'] ?? 'admin@homecatalog.ru';
        $subject = 'Новое сообщение с сайта';
        $body = '<strong>Email:</strong> ' . h($old['email']) . '<br>'
              . (!empty($old['phone']) ? '<strong>Телефон:</strong> ' . h($old['phone']) . '<br>' : '')
              . '<strong>Сообщение:</strong><br>' . nl2br(h($old['message']))
              . '<br><br><small>Отправлено через форму обратной связи</small>';
        $mailer->send($adminEmail, $subject, $body);

        flash_set('success', 'Сообщение отправлено! Мы свяжемся с вами в ближайшее время.');
        header('Location: /');
        exit;
    }
}

render_page('Свяжитесь с нами', breadcrumbs_static('Свяжитесь с нами'), function () use ($errors, $old) {
    ?>
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <h2>Свяжитесь с нами</h2>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <strong>Ошибки заполнения:</strong>
                    <ul class="mb-0 mt-1">
                        <?php foreach ($errors as $err): ?>
                            <li><?= h($err['message']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="/contact_us" id="contact-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                <div class="mb-3">
                    <label for="email" class="form-label">Ваш Email (обязательно):</label>
                    <input type="email" name="email" id="email"
                           class="form-control <?= validation_first_error($errors, 'email') ? 'is-invalid' : '' ?>"
                           maxlength="255" required value="<?= h($old['email']) ?>">
                    <?php if ($err = validation_first_error($errors, 'email')): ?>
                        <div class="invalid-feedback"><?= h($err) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">Телефон (необязательно):</label>
                    <input type="text" name="phone" id="phone"
                           class="form-control <?= validation_first_error($errors, 'phone') ? 'is-invalid' : '' ?>"
                           maxlength="64" value="<?= h($old['phone']) ?>">
                </div>

                <div class="mb-3">
                    <label for="message" class="form-label">Сообщение (обязательно):</label>
                    <textarea name="message" id="message"
                              class="form-control <?= validation_first_error($errors, 'message') ? 'is-invalid' : '' ?>"
                              rows="5" maxlength="5000" required><?= h($old['message']) ?></textarea>
                    <?php if ($err = validation_first_error($errors, 'message')): ?>
                        <div class="invalid-feedback"><?= h($err) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" name="agreement" id="agreement"
                           class="form-check-input <?= validation_first_error($errors, 'agreement') ? 'is-invalid' : '' ?>" required>
                    <label for="agreement" class="form-check-label">
                        Я согласен на обработку персональных данных
                    </label>
                    <?php if ($err = validation_first_error($errors, 'agreement')): ?>
                        <div class="invalid-feedback"><?= h($err) ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">Отправить</button>
            </form>
        </div>
    </div>
    <?php
});
