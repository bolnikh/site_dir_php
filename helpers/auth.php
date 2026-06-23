<?php

/**
 * Аутентификация модератора
 */

/**
 * Проверить авторизацию модератора.
 * Если не авторизован — редирект на страницу логина.
 */
function require_moderator(): void
{
    if (empty($_SESSION['moderator_id'])) {
        header('Location: /moderator/login');
        exit;
    }
}

/**
 * Вернуть данные текущего модератора или null
 */
function current_moderator(): ?array
{
    if (empty($_SESSION['moderator_id'])) {
        return null;
    }

    return [
        'id' => $_SESSION['moderator_id'],
        'username' => $_SESSION['moderator_username'] ?? '',
    ];
}
