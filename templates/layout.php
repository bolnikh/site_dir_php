<?php
/**
 * Общий шаблон страницы
 *
 * Ожидаемые переменные перед подключением:
 * @var array  $appConfig  — конфигурация приложения
 * @var string $pageTitle  — заголовок страницы
 * @var array  $breadcrumbs — хлебные крошки (результат breadcrumbs_*())
 * @var ?array $currentUser — данные текущего пользователя
 *
 * Содержимое страницы передаётся в переменной $content
 * и выводится между header и footer.
 */

// Подключаем шапку
require __DIR__ . '/header.php';

// Выводим содержимое страницы
if (isset($content)) {
    echo $content;
}

// Подключаем подвал
require __DIR__ . '/footer.php';
