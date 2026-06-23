<?php

return [
    'driver' => 'pgsql',
    'host' => getenv('DB_HOST') ?: 'postgres',
    'port' => getenv('DB_PORT') ?: 5432,
    'database' => getenv('DB_NAME') ?: 'catalog',
    'username' => getenv('DB_USER') ?: 'catalog_user',
    'password' => getenv('DB_PASSWORD') ?: 'FSY3hWw3NQJt',
    'charset' => 'utf8',
];
