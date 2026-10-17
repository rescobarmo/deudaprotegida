<?php
declare(strict_types=1);

require_once __DIR__ . '/api/auth.php';

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store, no-cache, must-revalidate');

if (is_authenticated()) {
    header('Location: ./');
    exit;
}

$error = '';
$notice = '';
$username = '';
$lockedSeconds = auth_is_locked();

if (isset($_SESSION['auth_notice']) && is_string($_SESSION['auth_notice'])) {
    $notice = $_SESSION['auth_notice'];
    unset($_SESSION['auth_notice']);
}

if (isset($_GET['logged_out'])) {
    $notice = 'Sesión cerrada correctamente.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) && is_string($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
    $token = isset($_POST['csrf']) && is_string($_POST['csrf']) ? $_POST['csrf'] : null;
    $lockedSeconds = auth_is_locked();

    if (!verify_csrf($token)) {
        $error = 'La sesión del formulario expiró. Vuelve a intentarlo.';
        csrf_rotate();
    } elseif ($lockedSeconds > 0) {
        $error = 'Demasiados intentos fallidos. Espera unos minutos antes de reintentar.';
    } elseif ($username === '' || $password === '') {
        $error = 'Ingresa tu usuario y tu clave.';
    } elseif (mb_strlen($username) > 100 || strlen($password) > 200) {
        $error = 'Los datos ingresados no son válidos.';
    } elseif (attempt_login($username, $password)) {
        header('Location: ./');
        exit;
    } else {
        $lockedSeconds = auth_is_locked();
        $error = $lockedSeconds > 0
            ? 'Demasiados intentos fallidos. El acceso quedó bloqueado temporalmente.'
            : 'Usuario o clave incorrectos.';
    }
}

$csrf = csrf_token();
$lockMinutes = $lockedSeconds > 0 ? (int) ceil($lockedSeconds / 60) : 0;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <meta name="theme-color" content="#020617">
  <title>Ingresar · Deuda Protegida</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/tailwind.css">
  <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-950 text-slate-900 antialiased">
  <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute h-0 w-0 overflow-hidden" aria-hidden="true">
    <symbol id="i-logo" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <path d="M12 3l7 3v5.2c0 4.4-2.9 8.3-7 9.5-4.1-1.2-7-5.1-7-9.5V6l7-3Z"/>
      <path d="M9 14v-2M12 14V9.5M15 14v-3.5"/>
    </symbol>
    <symbol id="i-lock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <rect x="4" y="10" width="16" height="11" rx="2"/>
      <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
    </symbol>
    <symbol id="i-trend" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <path d="M3 17l5-6 4 4 7-9"/>
      <path d="M15 6h4v4"/>
    </symbol>
    <symbol id="i-filter" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <path d="M4 6h16M7 12h10M10 18h4"/>
    </symbol>
    <symbol id="i-download" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <path d="M12 3v12M7 10l5 5 5-5"/>
      <path d="M5 21h14"/>
    </symbol>
    <symbol id="i-database" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <ellipse cx="12" cy="5" rx="8" ry="3"/>
      <path d="M4 5v6c0 1.66 3.58 3 8 3s8-1.34 8-3V5"/>
      <path d="M4 11v6c0 1.66 3.58 3 8 3s8-1.34 8-3v-6"/>
    </symbol>
    <symbol id="i-alert" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <circle cx="12" cy="12" r="9"/>
      <path d="M12 8v5M12 16.5v.01"/>
    </symbol>
    <symbol id="i-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <circle cx="12" cy="12" r="9"/>
      <path d="m8.5 12 2.5 2.5 4.5-5"/>
    </symbol>
  </svg>

  <main class="grid min-h-screen lg:grid-cols-[1.05fr_1fr]">
    <section class="relative hidden overflow-hidden bg-slate-950 p-12 lg:flex lg:flex-col lg:justify-between">
      <div class="pointer-events-none absolute -left-24 top-16 h-80 w-80 rounded-full bg-indigo-500/20 blur-3xl"></div>
      <div class="pointer-events-none absolute -right-16 bottom-10 h-72 w-72 rounded-full bg-teal-400/10 blur-3xl"></div>
      <div class="relative flex items-center gap-3">
        <div class="grid h-11 w-11 place-items-center rounded-xl bg-gradient-to-br from-indigo-500 to-teal-400 text-white shadow-lg shadow-indigo-500/20">
          <svg class="h-6 w-6"><use href="#i-logo"></use></svg>
        </div>
        <div>
          <p class="display text-base font-extrabold tracking-tight text-white">Totemly</p>
          <p class="text-xs text-slate-400">Deuda Protegida</p>
        </div>
      </div>

      <div class="relative max-w-lg">
        <span class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-semibold text-teal-300">
          <span class="h-1.5 w-1.5 rounded-full bg-teal-400"></span>
          Inteligencia de leads
        </span>
        <h2 class="display mt-5 text-4xl font-extrabold leading-[1.1] tracking-tight text-white">
          Decisiones comerciales con datos claros.
        </h2>
        <p class="mt-4 max-w-md text-sm leading-6 text-slate-400">
          Accede al panel gerencial de Deuda Protegida para revisar leads, montos, tendencias y oportunidades en un solo lugar.
        </p>
        <ul class="mt-8 space-y-4">
          <li class="flex items-start gap-3">
            <span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-indigo-500/15 text-indigo-300">
              <svg class="h-4 w-4"><use href="#i-trend"></use></svg>
            </span>
            <div>
              <p class="text-sm font-semibold text-white">Seguimiento en tiempo real</p>
              <p class="text-xs text-slate-400">KPIs y tendencias actualizadas desde MySQL.</p>
            </div>
          </li>
          <li class="flex items-start gap-3">
            <span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-teal-500/15 text-teal-300">
              <svg class="h-4 w-4"><use href="#i-filter"></use></svg>
            </span>
            <div>
              <p class="text-sm font-semibold text-white">Filtros gerenciales</p>
              <p class="text-xs text-slate-400">Analiza por fecha, monto, nombre o RUT.</p>
            </div>
          </li>
          <li class="flex items-start gap-3">
            <span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-sky-500/15 text-sky-300">
              <svg class="h-4 w-4"><use href="#i-download"></use></svg>
            </span>
            <div>
              <p class="text-sm font-semibold text-white">Información exportable</p>
              <p class="text-xs text-slate-400">Descarga los leads filtrados en CSV.</p>
            </div>
          </li>
        </ul>
      </div>

      <div class="relative flex items-center gap-2 text-[11px] text-slate-500">
        <svg class="h-3.5 w-3.5"><use href="#i-database"></use></svg>
        MySQL · totemly · Acceso restringido
      </div>
    </section>

    <section class="flex items-center justify-center bg-[#f5f7fb] px-5 py-10 sm:px-8">
      <div class="w-full max-w-md">
        <div class="mb-8 flex items-center gap-3 lg:hidden">
          <div class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-indigo-500 to-teal-400 text-white shadow-lg shadow-indigo-500/20">
            <svg class="h-5 w-5"><use href="#i-logo"></use></svg>
          </div>
          <div>
            <p class="display text-sm font-extrabold tracking-tight">Totemly</p>
            <p class="text-xs text-slate-500">Deuda Protegida</p>
          </div>
        </div>

        <div class="card p-7 sm:p-9">
          <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-300">
            <svg class="h-5 w-5"><use href="#i-lock"></use></svg>
          </span>
          <h1 class="display mt-5 text-2xl font-extrabold tracking-tight">Inicia sesión</h1>
          <p class="mt-1.5 text-sm text-slate-500">Usa tus credenciales para acceder al panel analítico.</p>

          <?php if ($error !== ''): ?>
            <div class="mt-5 flex items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700" role="alert">
              <svg class="mt-0.5 h-4 w-4 shrink-0"><use href="#i-alert"></use></svg>
              <p class="leading-5"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
          <?php elseif ($notice !== ''): ?>
            <div class="mt-5 flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700" role="status">
              <svg class="mt-0.5 h-4 w-4 shrink-0"><use href="#i-check"></use></svg>
              <p class="leading-5"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
          <?php elseif ($lockMinutes > 0): ?>
            <div class="mt-5 flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-700" role="alert">
              <svg class="mt-0.5 h-4 w-4 shrink-0"><use href="#i-alert"></use></svg>
              <p class="leading-5">Acceso bloqueado temporalmente. Reintenta en <?= (int) $lockMinutes ?> minuto<?= $lockMinutes === 1 ? '' : 's' ?>.</p>
            </div>
          <?php endif; ?>

          <form method="post" action="login.php" class="mt-6" autocomplete="on">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

            <label class="field-label" for="username">Usuario</label>
            <input
              id="username"
              name="username"
              type="text"
              class="field"
              value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>"
              autocomplete="username"
              maxlength="100"
              required
              autofocus
            >

            <label class="field-label mt-4" for="password">Clave</label>
            <div class="relative">
              <input
                id="password"
                name="password"
                type="password"
                class="field"
                style="padding-right:5rem"
                autocomplete="current-password"
                maxlength="200"
                required
              >
              <button
                id="toggle-password"
                type="button"
                class="absolute right-2 top-1/2 -translate-y-1/2 rounded-md px-2 py-1 text-xs font-semibold text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                aria-label="Mostrar clave"
                aria-pressed="false"
              >Mostrar</button>
            </div>

            <button type="submit" class="btn btn-primary mt-5 w-full">
              <svg class="h-4 w-4"><use href="#i-lock"></use></svg>
              Ingresar al panel
            </button>
          </form>

          <p class="mt-5 border-t border-slate-100 pt-4 text-center text-[11px] leading-5 text-slate-400">
            Acceso exclusivo para personal autorizado. Las credenciales son administradas por Totemly.
          </p>
        </div>

        <p class="mt-6 text-center text-[11px] text-slate-400">
          © <?= date('Y') ?> Totemly · Deuda Protegida
        </p>
      </div>
    </section>
  </main>

  <script>
    (function () {
      var input = document.getElementById("password");
      var button = document.getElementById("toggle-password");
      button.addEventListener("click", function () {
        var visible = input.type === "password";
        input.type = visible ? "text" : "password";
        button.textContent = visible ? "Ocultar" : "Mostrar";
        button.setAttribute("aria-pressed", visible ? "true" : "false");
        button.setAttribute("aria-label", visible ? "Ocultar clave" : "Mostrar clave");
        input.focus();
      });
    })();
  </script>
</body>
</html>
