<?php

/**
 * Формирование хлебных крошек
 */

/**
 * @param array $items [['title' => '...', 'url' => '...|null'], ...]
 * @return array
 */
function breadcrumbs_generate(array $items): array
{
    $crumbs = [];

    // Всегда начинаем с «Начало»
    $crumbs[] = [
        'title' => 'Начало',
        'url' => '/',
    ];

    foreach ($items as $item) {
        $crumbs[] = [
            'title' => $item['title'],
            'url' => $item['url'] ?? null, // null = текущая страница (без ссылки)
        ];
    }

    return $crumbs;
}

/**
 * Получить хлебные крошки для раздела
 */
function breadcrumbs_section(array $ancestors, array $current): array
{
    $items = [];

    foreach ($ancestors as $ancestor) {
        $items[] = [
            'title' => $ancestor['name'],
            'url' => '/section/' . $ancestor['slug'],
        ];
    }

    $items[] = [
        'title' => $current['name'],
        'url' => null, // текущий раздел — без ссылки
    ];

    return breadcrumbs_generate($items);
}

/**
 * Получить хлебные крошки для страницы сайта
 */
function breadcrumbs_site(array $sectionPath, array $site): array
{
    $items = [];

    foreach ($sectionPath as $sec) {
        $items[] = [
            'title' => $sec['name'],
            'url' => '/section/' . $sec['slug'],
        ];
    }

    $items[] = [
        'title' => $site['name'],
        'url' => null, // текущая страница — без ссылки
    ];

    return breadcrumbs_generate($items);
}

/**
 * Получить хлебные крошки для статической страницы
 */
function breadcrumbs_static(string $title): array
{
    $items = [
        ['title' => $title, 'url' => null],
    ];

    return breadcrumbs_generate($items);
}
