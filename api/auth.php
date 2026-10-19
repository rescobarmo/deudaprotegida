<?php
declare(strict_types=1);

function auth_settings(): array
{
    static $settings = null;
    if ($settings !== null) {
        return $settings;
    }

    $config = require __DIR__ . '/config.php';
    $auth = isset($config['auth']) && is_array($config['auth']) ? $config['auth'] : [];
    $defaults = [
        'username' => 'admin',
        'password_hash' => '',
        'session_name' => 'DEUDAPROTEGIDA',
        'idle_timeout' => 7200,
        'max_attempts' => 5,
        'lockout_seconds' => 300,
    ];

    $settings = array_merge($defaults, $auth);
    $settings['idle_timeout'] = max(0, (int) $settings['idle_timeout']);
    $settings['max_attempts'] = max(1, (int) $settings['max_attempts']);
    $settings['lockout_seconds'] = max(30, (int) $settings['lockout_seconds']);

    return $settings;
}

function auth_cookie_path(): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/'));
    $directory = rtrim(str_replace('\\', '/', dirname($script)), '/');

    if (str_ends_with($directory, '/api')) {
        $directory = substr($directory, 0, -4);
    }

    return $directory === '' || $directory === '.' ? '/' : $directory . '/';
}

function auth_boot(): void
{
    static $booted = false;
    if ($booted) {
        return;
    }

    $settings = auth_settings();

    if (session_status() !== PHP_SESSION_ACTIVE) {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.gc_maxlifetime', (string) max(1440, (int) $settings['idle_timeout']));

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';

        session_name((string) $settings['session_name']);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => auth_cookie_path(),
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_cache_limiter('nocache');
        session_start();
    }

    $booted = true;

    if (isset($_SESSION['auth_user'], $_SESSION['last_activity'])) {
        $currentFingerprint = hash('sha256', (string) $settings['password_hash']);
        $sessionFingerprint = is_string($_SESSION['auth_fingerprint'] ?? null) ? $_SESSION['auth_fingerprint'] : '';
        $sameUser = is_string($_SESSION['auth_user']) && hash_equals((string) $settings['username'], $_SESSION['auth_user']);

        if (!$sameUser || !hash_equals($currentFingerprint, $sessionFingerprint)) {
            auth_clear_identity();
            $_SESSION['auth_notice'] = 'Las credenciales cambiaron. Ingresa nuevamente.';
        } else {
            $idleTimeout = (int) $settings['idle_timeout'];
            if ($idleTimeout > 0 && time() - (int) $_SESSION['last_activity'] > $idleTimeout) {
                auth_clear_identity();
                $_SESSION['auth_notice'] = 'Tu sesión expiró por inactividad. Ingresa nuevamente.';
            } else {
                $_SESSION['last_activity'] = time();
            }
        }
    }
}

function auth_clear_identity(): void
{
    unset(
        $_SESSION['auth_user'],
        $_SESSION['auth_time'],
        $_SESSION['last_activity'],
        $_SESSION['auth_fingerprint'],
        $_SESSION['csrf_token']
    );
}

function is_authenticated(): bool
{
    auth_boot();
    return isset($_SESSION['auth_user']) && is_string($_SESSION['auth_user']);
}

function current_user(): ?string
{
    auth_boot();
    return is_string($_SESSION['auth_user'] ?? null) ? $_SESSION['auth_user'] : null;
}

function require_auth_page(): void
{
    auth_boot();
    if (is_authenticated()) {
        return;
    }
    header('Location: index.php');
    exit;
}

function require_auth_api(): void
{
    auth_boot();
    if (is_authenticated()) {
        return;
    }

    $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
    $wantsDocument = str_contains($accept, 'text/html') && !str_contains($accept, 'application/json');

    if ($wantsDocument) {
        header('Location: ../index.php');
        exit;
    }

    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => false,
        'error' => [
            'message' => 'Sesión expirada o no autenticada.',
            'code' => 'AUTH_REQUIRED',
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function csrf_token(): string
{
    auth_boot();
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || strlen($_SESSION['csrf_token']) < 32) {
        csrf_rotate();
    }
    return $_SESSION['csrf_token'];
}

function csrf_rotate(): string
{
    auth_boot();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): bool
{
    auth_boot();
    $expected = $_SESSION['csrf_token'] ?? '';
    return is_string($token) && is_string($expected) && $expected !== '' && hash_equals($expected, $token);
}

function auth_is_locked(): int
{
    auth_boot();
    $lockedUntil = (int) ($_SESSION['auth_locked_until'] ?? 0);
    if ($lockedUntil > time()) {
        return $lockedUntil - time();
    }
    if ($lockedUntil > 0) {
        unset($_SESSION['auth_locked_until']);
        $_SESSION['auth_attempts'] = 0;
    }
    return 0;
}

function auth_register_failure(): void
{
    auth_boot();
    $settings = auth_settings();
    $attempts = (int) ($_SESSION['auth_attempts'] ?? 0) + 1;

    if ($attempts >= (int) $settings['max_attempts']) {
        $_SESSION['auth_attempts'] = 0;
        $_SESSION['auth_locked_until'] = time() + (int) $settings['lockout_seconds'];
        return;
    }

    $_SESSION['auth_attempts'] = $attempts;
}

function attempt_login(string $username, string $password): bool
{
    auth_boot();
    if (auth_is_locked() > 0) {
        return false;
    }

    $settings = auth_settings();
    $validUser = hash_equals((string) $settings['username'], $username);
    $validPassword = password_verify($password, (string) $settings['password_hash']);

    if ($validUser && $validPassword) {
        session_regenerate_id(true);
        $_SESSION['auth_user'] = (string) $settings['username'];
        $_SESSION['auth_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['auth_fingerprint'] = hash('sha256', (string) $settings['password_hash']);
        unset($_SESSION['auth_attempts'], $_SESSION['auth_locked_until'], $_SESSION['auth_notice']);
        csrf_rotate();
        return true;
    }

    auth_register_failure();
    usleep(300000);
    return false;
}

function auth_logout(): void
{
    auth_boot();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'] ?: '/',
            'domain' => $params['domain'] ?? '',
            'secure' => (bool) ($params['secure'] ?? false),
            'httponly' => (bool) ($params['httponly'] ?? true),
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }

    session_destroy();
}
