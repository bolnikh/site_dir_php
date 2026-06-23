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

/**
 * Отрендерить HTML постраничной навигации (Bootstrap)
 */
function render_pagination(int $currentPage, int $totalPages, string $baseUrl, array $queryParams = []): string
{
    if ($totalPages <= 1) {
        return '';
    }

    $currentPage = max(1, min($currentPage, $totalPages));
    $html = '<nav class="pagination-nav" aria-label="Навигация по страницам">';
    $html .= '<ul class="pagination justify-content-center">';

    // Кнопка «Предыдущая»
    if ($currentPage > 1) {
        $prevUrl = pagination_url($baseUrl, $currentPage - 1, $queryParams);
        $html .= '<li class="page-item"><a class="page-link" href="' . h($prevUrl) . '" aria-label="Предыдущая">&laquo;</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">&laquo;</span></li>';
    }

    // Номера страниц
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);

    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . h(pagination_url($baseUrl, 1, $queryParams)) . '">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        if ($i === $currentPage) {
            $html .= '<li class="page-item active" aria-current="page"><span class="page-link">' . $i . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . h(pagination_url($baseUrl, $i, $queryParams)) . '">' . $i . '</a></li>';
        }
    }

    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . h(pagination_url($baseUrl, $totalPages, $queryParams)) . '">' . $totalPages . '</a></li>';
    }

    // Кнопка «Следующая»
    if ($currentPage < $totalPages) {
        $nextUrl = pagination_url($baseUrl, $currentPage + 1, $queryParams);
        $html .= '<li class="page-item"><a class="page-link" href="' . h($nextUrl) . '" aria-label="Следующая">&raquo;</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">&raquo;</span></li>';
    }

    $html .= '</ul>';
    $html .= '<div class="text-center text-muted small">Страница ' . $currentPage . ' из ' . $totalPages . '</div>';
    $html .= '</nav>';

    return $html;
}
