<?php

/**
 * Flash-сообщения через сессию
 */

/**
 * Сохранить flash-сообщение
 */
function flash_set(string $type, string $message): void
{
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }

    $_SESSION['flash'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

/**
 * Получить и очистить все flash-сообщения
 */
function flash_get(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}
