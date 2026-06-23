<?php
/**
 * Фронт-контроллер — точка входа для всех запросов
 */
session_start();

require_once __DIR__ . '/../vendor/autoload.php';

// Загрузка конфигов
$appConfig = require __DIR__ . '/../config/app.php';
date_default_timezone_set($appConfig['timezone']);

// Подключение к БД
$db = new \App\Database(require __DIR__ . '/../config/database.php');

// Инициализация кэша
$cache = new \App\Cache(require __DIR__ . '/../config/redis.php');

// Инициализация почты
$mailer = new \App\Mailer(require __DIR__ . '/../config/mail.php');

// Подключение шаблона (render_page)
require_once __DIR__ . '/../templates/layout.php';

// Роутинг
$page = $_GET['page'] ?? 'home';
$slug = $_GET['slug'] ?? null;
$id = $_GET['id'] ?? null;

// Карта маршрутов
$routes = [
    'home'                    => __DIR__ . '/../pages/home.php',
    'section'                 => __DIR__ . '/../pages/section.php',
    'site'                    => __DIR__ . '/../pages/site.php',
    'about'                   => __DIR__ . '/../pages/about.php',
    'rules'                   => __DIR__ . '/../pages/rules.php',
    'add'                     => __DIR__ . '/../pages/add.php',
    'moderator/login'         => __DIR__ . '/../moderator/login.php',
    'moderator/logout'        => __DIR__ . '/../moderator/logout.php',
    'moderator/list'          => __DIR__ . '/../moderator/list.php',
    'moderator/moderate'      => __DIR__ . '/../moderator/moderate.php',
    'moderator/sections'      => __DIR__ . '/../moderator/sections.php',
    'moderator/sections/add'  => __DIR__ . '/../moderator/section_add.php',
    'moderator/sections/edit' => __DIR__ . '/../moderator/section_edit.php',
];

if (isset($routes[$page])) {
    require $routes[$page];
} else {
    http_response_code(404);
    echo '<h1>404 — Страница не найдена</h1>';
}
