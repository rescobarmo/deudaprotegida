<?php
declare(strict_types=1);

return [
    'db' => [
        'host' => getenv('DEUDA_DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DEUDA_DB_PORT') ?: '3306',
        'name' => getenv('DEUDA_DB_NAME') ?: 'totemly',
        'user' => getenv('DEUDA_DB_USER') ?: 'root',
        'pass' => getenv('DEUDA_DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'table_candidates' => array_values(array_filter([
        getenv('DEUDA_TABLE') ?: null,
        'datos_deudaprotegida',
        'datos_deudaprotegina',
    ])),
    'timezone' => getenv('DEUDA_TIMEZONE') ?: 'America/Santiago',
    'debug' => filter_var(getenv('DEUDA_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN),
    'auth' => [
        'username' => getenv('DEUDA_AUTH_USER') ?: 'admin',
        'password_hash' => getenv('DEUDA_AUTH_PASS_HASH') ?: '',
        'session_name' => getenv('DEUDA_AUTH_SESSION') ?: 'DEUDAPROTEGIDA',
        'idle_timeout' => (int) (getenv('DEUDA_AUTH_IDLE') ?: 7200),
        'max_attempts' => (int) (getenv('DEUDA_AUTH_MAX_ATTEMPTS') ?: 5),
        'lockout_seconds' => (int) (getenv('DEUDA_AUTH_LOCKOUT') ?: 300),
    ],
];
