<?php

/**
 * Постраничная навигация
 */

/**
 * Сгенерировать данные для пагинации
 */
function pagination_generate(int $currentPage, int $totalItems, int $perPage, string $baseUrl, array $queryParams = []): array
{
    $totalPages = max(1, (int) ceil($totalItems / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));

    $pages = [];
    for ($i = 1; $i <= $totalPages; $i++) {
        $pages[] = [
            'number' => $i,
            'url' => pagination_url($baseUrl, $i, $queryParams),
            'active' => $i === $currentPage,
        ];
    }

    return [
        'current' => $currentPage,
        'total' => $totalPages,
        'total_items' => $totalItems,
        'per_page' => $perPage,
        'pages' => $pages,
        'has_previous' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
        'previous_url' => $currentPage > 1 ? pagination_url($baseUrl, $currentPage - 1, $queryParams) : null,
        'next_url' => $currentPage < $totalPages ? pagination_url($baseUrl, $currentPage + 1, $queryParams) : null,
        'first_item' => ($currentPage - 1) * $perPage + 1,
        'last_item' => min($currentPage * $perPage, $totalItems),
    ];
}

function pagination_url(string $baseUrl, int $page, array $queryParams = []): string
{
    $params = array_merge($queryParams, ['page' => $page]);
    return $baseUrl . '?' . http_build_query($params);
}

/**
 * SQL LIMIT/OFFSET для пагинации
 */
function pagination_sql(int $page, int $perPage): string
{
    $offset = ($page - 1) * $perPage;
    return "LIMIT {$perPage} OFFSET {$offset}";
}
