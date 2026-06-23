<?php

return [
    'host' => getenv('REDIS_HOST') ?: 'redis',
    'port' => getenv('REDIS_PORT') ?: 6379,
    'prefix' => 'catalog:',
];
