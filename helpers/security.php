<?php

/**
 * Безопасность: XSS-защита и вспомогательные функции
 */

/**
 * Экранирование строки для безопасного вывода в HTML
 * Всегда используйте эту функцию для вывода пользовательских данных
 */
function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Экранирование для использования в атрибутах HTML
 */
function h_attr(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Очистка HTML через HTMLPurifier
 * Для безопасного вывода описания сайта (HTML из редактора)
 */
function purify_html(string $dirtyHtml): string
{
    static $purifier = null;

    if ($purifier === null) {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'b,i,u,strong,em,a[href|title],ul,ol,li,p,br,blockquote,h1,h2,h3,h4,h5,h6,span,div');
        $config->set('CSS.AllowedProperties', '');
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('HTML.Nofollow', true);

        $purifier = new HTMLPurifier($config);
    }

    return $purifier->purify($dirtyHtml);
}

/**
 * Очистка HTML (синоним purify_html, согласно спецификации шага 4)
 */
function clean_html(string $html): string
{
    return purify_html($html);
}

/**
 * CSRF-токен для форм
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Проверка CSRF-токена
 */
function csrf_verify(string $token): bool
{
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}
