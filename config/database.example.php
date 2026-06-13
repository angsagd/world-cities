<?php

declare(strict_types=1);

return [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'port' => (int) (getenv('DB_PORT') ?: 3306),
    'database' => getenv('DB_DATABASE') ?: 'world',
    'username' => getenv('DB_USERNAME') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'socket' => getenv('DB_SOCKET') ?: null,
    'charset' => 'utf8mb4',
];
