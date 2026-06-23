<?php

return [
    'name' => 'Каталог сайтов',
    'url' => 'http://homecatalog.ru',
    'sites_per_page' => 20,
    'recent_sites_count' => 10,
    'debug' => filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN),
    'timezone' => 'Europe/Moscow',
];
