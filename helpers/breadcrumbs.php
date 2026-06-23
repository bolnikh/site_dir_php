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

/**
 * Построить хлебные крошки из массива элементов
 * Принимает: [['label' => 'Начало', 'url' => '/'], ['label' => 'Раздел', 'url' => null], ...]
 * Последний элемент — без ссылки (текущая страница).
 */
function build_breadcrumbs(\PDO $db, array $items): array
{
    $crumbs = [];

    // Всегда начинаем с «Начало»
    $crumbs[] = [
        'title' => 'Начало',
        'url' => '/',
    ];

    foreach ($items as $item) {
        $crumbs[] = [
            'title' => $item['label'] ?? $item['title'] ?? '',
            'url' => $item['url'] ?? null,
        ];
    }

    return $crumbs;
}

/**
 * Построить цепочку хлебных крошек от корня до указанного раздела
 * Использует поле path в таблице sections
 */
function breadcrumbs_from_path(\PDO $db, int $sectionId): array
{
    $section = $db->query(
        'SELECT id, parent_id, path, name, slug FROM sections WHERE id = ?',
        [$sectionId]
    )->fetch();

    if (!$section) {
        return breadcrumbs_generate([['title' => 'Раздел не найден', 'url' => null]]);
    }

    // Получаем ID всех родителей из path
    $pathIds = array_filter(explode('/', $section['path']));
    $items = [];

    if (!empty($pathIds)) {
        // Убираем последний элемент (текущий раздел) из цепочки ссылок
        $parentIds = array_slice($pathIds, 0, -1);

        if (!empty($parentIds)) {
            $placeholders = implode(',', array_fill(0, count($parentIds), '?'));
            $stmt = $db->query(
                "SELECT id, name, slug FROM sections WHERE id IN ({$placeholders}) ORDER BY id",
                $parentIds
            );
            $ancestors = $stmt->fetchAll();

            foreach ($ancestors as $ancestor) {
                $items[] = [
                    'title' => $ancestor['name'],
                    'url' => '/section/' . $ancestor['slug'],
                ];
            }
        }
    }

    // Текущий раздел — без ссылки
    $items[] = [
        'title' => $section['name'],
        'url' => null,
    ];

    return breadcrumbs_generate($items);
}
