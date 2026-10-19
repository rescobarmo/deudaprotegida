<?php
declare(strict_types=1);

require_once __DIR__ . '/api/auth.php';

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store, no-cache, must-revalidate');

if (!is_authenticated()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$token = isset($_POST['csrf']) && is_string($_POST['csrf']) ? $_POST['csrf'] : null;
if (!verify_csrf($token)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Solicitud inválida.';
    exit;
}

auth_logout();
header('Location: index.php?logged_out=1');
exit;
