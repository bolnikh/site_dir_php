<?php
/**
 * Страница входа модератора — /moderator/login
 */

$pageTitle = 'Вход для модератора';
$breadcrumbs = breadcrumbs_static('Вход');
$currentUser = null;

// TODO: Обработка формы входа (шаг 11)

ob_start();
?>
<h2>Вход для модератора</h2>

<!-- TODO: Форма логина (шаг 11) -->

<div class="alert alert-info">
    Форма входа будет реализована на шаге 11.
</div>

<?php
$content = ob_get_clean();

require __DIR__ . '/../templates/layout.php';
