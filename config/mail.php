<?php

return [
    'host' => getenv('SMTP_HOST') ?: 'smtp.example.com',
    'port' => getenv('SMTP_PORT') ?: 587,
    'username' => getenv('SMTP_USER') ?: 'noreply@homecatalog.ru',
    'password' => getenv('SMTP_PASS') ?: '',
    'encryption' => getenv('SMTP_ENCRYPTION') ?: 'tls',
    'from' => getenv('SMTP_USER') ?: 'noreply@homecatalog.ru',
    'from_name' => 'Каталог сайтов',
];
