<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function app_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/config.php';
    }
    return $config;
}

require_once __DIR__ . '/db.php';

date_default_timezone_set((string) app_config()['timezone']);
mb_internal_encoding('UTF-8');
error_reporting(E_ALL);
ini_set('display_errors', '0');

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store, max-age=0');

set_exception_handler(static function (Throwable $exception): void {
    error_log(
        '[deudaprotegida] ' . get_class($exception) . ': ' . $exception->getMessage()
        . ' in ' . $exception->getFile() . ':' . $exception->getLine()
    );

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }

    $message = app_config()['debug']
        ? $exception->getMessage()
        : 'No fue posible procesar la solicitud.';

    echo json_encode([
        'ok' => false,
        'error' => ['message' => $message],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
});

require_once __DIR__ . '/auth.php';
require_auth_api();
