<?php
declare(strict_types=1);

require_once __DIR__ . '/api/auth.php';
require_auth_page();

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store, no-cache, must-revalidate');

$authUser = current_user() ?? '';
$authCsrf = csrf_token();
?>
<!doctype html>
<html lang="es" class="scroll-smooth">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Dashboard analítico de leads de Deuda Protegida sobre MySQL.">
  <meta name="robots" content="noindex, nofollow">
  <meta name="theme-color" content="#020617">
  <title>Deuda Protegida · Panel de leads</title>
  <script>
    (function () {
      try {
        var saved = localStorage.getItem("dp-theme");
        if (saved === "dark" || (!saved && window.matchMedia("(prefers-color-scheme: dark)").matches)) {
          document.documentElement.classList.add("dark");
        }
      } catch (error) {
        document.documentElement.classList.remove("dark");
      }
    })();
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/tailwind.css">
  <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="bg-[#f5f7fb] text-slate-900 antialiased dark:bg-[#020617] dark:text-slate-100">
  <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute h-0 w-0 overflow-hidden" aria-hidden="true">
    <symbol id="i-dashboard" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <rect x="3" y="3" width="7" height="7" rx="1.5"/>
      <rect x="14" y="3" width="7" height="7" rx="1.5"/>
      <rect x="3" y="14" width="7" height="7" rx="1.5"/>
      <rect x="14" y="14" width="7" height="7" rx="1.5"/>
    </symbol>
    <symbol id="i-trend" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <path d="M3 17l5-6 4 4 7-9"/>
      <path d="M15 6h4v4"/>
    </symbol>
    <symbol id="i-pie" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <path d="M21 12a9 9 0 1 1-9-9v9h9Z"/>
      <path d="M15 3.5A9 9 0 0 1 20.5 9H15V3.5Z"/>
    </symbol>
    <symbol id="i-table" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <rect x="3" y="4" width="18" height="16" rx="2"/>
      <path d="M3 10h18M9 10v10"/>
    </symbol>
    <symbol id="i-menu" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <path d="M4 7h16M4 12h16M4 17h16"/>
    </symbol>
    <symbol id="i-x" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <path d="M6 6l12 12M18 6 6 18"/>
    </symbol>
    <symbol id="i-refresh" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <path d="M20 11a8 8 0 1 0-2.34 5.66"/>
      <path d="M20 4v7h-7"/>
    </symbol>
    <symbol id="i-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <circle cx="12" cy="12" r="4"/>
      <path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41"/>
    </symbol>
    <symbol id="i-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <path d="M20 14.5A8.5 8.5 0 1 1 9.5 4a7 7 0 0 0 10.5 10.5Z"/>
    </symbol>
    <symbol id="i-filter" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <path d="M4 6h16M7 12h10M10 18h4"/>
    </symbol>
    <symbol id="i-calendar" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <rect x="3" y="5" width="18" height="16" rx="2"/>
      <path d="M16 3v4M8 3v4M3 10h18"/>
    </symbol>
    <symbol id="i-calendar-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <rect x="3" y="5" width="18" height="16" rx="2"/>
      <path d="M16 3v4M8 3v4M3 10h18M9 15l2 2 4-4"/>
    </symbol>
    <symbol id="i-search" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <circle cx="11" cy="11" r="7"/>
      <path d="m20 20-3.5-3.5"/>
    </symbol>
    <symbol id="i-download" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <path d="M12 3v12M7 10l5 5 5-5"/>
      <path d="M5 21h14"/>
    </symbol>
    <symbol id="i-chevron-left" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <path d="m15 18-6-6 6-6"/>
    </symbol>
    <symbol id="i-chevron-right" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <path d="m9 18 6-6-6-6"/>
    </symbol>
    <symbol id="i-inbox" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <path d="M4 13 6 5h12l2 8"/>
      <path d="M4 13h5l1.5 2h3L15 13h5v6H4v-6Z"/>
    </symbol>
    <symbol id="i-users" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
      <circle cx="9" cy="7" r="4"/>
      <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
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
    <symbol id="i-sparkles" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <path d="M12 3l1.2 3.8L17 8l-3.8 1.2L12 13l-1.2-3.8L7 8l3.8-1.2L12 3Z"/>
      <path d="M19 14l.7 2.3L22 17l-2.3.7L19 20l-.7-2.3L16 17l2.3-.7L19 14Z"/>
    </symbol>
    <symbol id="i-logout" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
      <path d="m16 17 5-5-5-5M21 12H9"/>
    </symbol>
    <symbol id="i-whatsapp" viewBox="0 0 24 24" fill="currentColor">
      <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.149-.174.198-.298.297-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/>
    </symbol>
  </svg>

  <div id="sidebar-overlay" class="fixed inset-0 z-30 hidden bg-slate-950/60 backdrop-blur-sm lg:hidden"></div>

  <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col bg-slate-950 text-slate-300 shadow-2xl transition-transform duration-300 lg:translate-x-0" aria-label="Navegación principal">
    <div class="flex h-20 items-center gap-3 border-b border-white/5 px-5">
      <a href="dashboard.php" class="min-w-0">
        <img src="assets/img/logo.svg" alt="Deuda Protegida" class="logo-invert h-10 w-auto">
      </a>
      <button id="sidebar-close" type="button" class="ml-auto grid h-8 w-8 place-items-center rounded-lg text-slate-400 hover:bg-white/10 hover:text-white lg:hidden" aria-label="Cerrar menú">
        <svg class="h-4 w-4"><use href="#i-x"></use></svg>
      </button>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-5">
      <p class="px-3 pb-2 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Analítica</p>
      <a class="sidebar-link" data-active="true" href="#resumen">
        <svg class="h-4 w-4"><use href="#i-dashboard"></use></svg>
        <span>Resumen</span>
      </a>
      <a class="sidebar-link" href="#tendencia">
        <svg class="h-4 w-4"><use href="#i-trend"></use></svg>
        <span>Tendencia</span>
      </a>
      <a class="sidebar-link" href="#distribucion">
        <svg class="h-4 w-4"><use href="#i-pie"></use></svg>
        <span>Distribución</span>
      </a>
      <a class="sidebar-link" href="#leads">
        <svg class="h-4 w-4"><use href="#i-table"></use></svg>
        <span>Leads</span>
      </a>
      <p class="px-3 pb-2 pt-5 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Fuente</p>
      <div class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-xs text-slate-400">
        <svg class="h-4 w-4"><use href="#i-database"></use></svg>
        <span>MySQL · totemly</span>
      </div>
    </nav>

    <div class="p-3">
      <div class="mb-3 flex items-center gap-2.5 rounded-xl border border-white/10 bg-white/5 p-2.5">
        <div class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-gradient-to-br from-indigo-500 to-teal-400 text-xs font-extrabold text-white">
          <?= htmlspecialchars(strtoupper(mb_substr($authUser, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div class="min-w-0 flex-1">
          <p class="truncate text-xs font-semibold text-white"><?= htmlspecialchars($authUser, ENT_QUOTES, 'UTF-8') ?></p>
          <p class="text-[10px] text-slate-500">Administrador</p>
        </div>
        <form method="post" action="logout.php">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($authCsrf, ENT_QUOTES, 'UTF-8') ?>">
          <button type="submit" class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 transition hover:bg-white/10 hover:text-rose-300" title="Cerrar sesión" aria-label="Cerrar sesión">
            <svg class="h-4 w-4"><use href="#i-logout"></use></svg>
          </button>
        </form>
      </div>
      <div class="rounded-xl border border-white/10 bg-white/5 p-3">
        <div class="flex items-center gap-2">
          <span id="db-dot" class="db-dot"></span>
          <span id="db-status" class="text-xs font-semibold text-white">Conectando…</span>
        </div>
        <p class="mt-1.5 text-[11px] text-slate-400">Base de datos operativa</p>
        <p id="sidebar-updated" class="mt-0.5 text-[11px] text-slate-500">Conectando…</p>
      </div>
      <p class="mt-3 px-1 text-[10px] text-slate-600">Panel gerencial · v1.0</p>
    </div>
  </aside>

  <div class="min-h-screen lg:pl-72">
    <header class="sticky top-0 z-20 border-b border-slate-200/70 bg-[#f5f7fb]/85 backdrop-blur-xl dark:border-slate-800/80 dark:bg-slate-950/80">
      <div class="flex items-center gap-3 px-4 py-4 sm:px-6 xl:px-8">
        <button id="menu-btn" type="button" class="btn btn-ghost btn-icon lg:hidden" aria-label="Abrir menú" aria-expanded="false" aria-controls="sidebar">
          <svg class="h-5 w-5"><use href="#i-menu"></use></svg>
        </button>
        <div class="min-w-0 flex-1">
          <div class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">
            <span>Totemly</span>
            <span class="text-slate-300 dark:text-slate-600">/</span>
            <span>Analytics</span>
          </div>
          <h1 class="display truncate text-xl font-extrabold tracking-tight sm:text-2xl">Deuda Protegida</h1>
        </div>
        <div class="hidden text-right sm:block">
          <p class="text-[10px] font-semibold uppercase tracking-[0.1em] text-slate-400">Última actualización</p>
          <p id="last-updated" class="text-xs font-semibold text-slate-600 dark:text-slate-300">Cargando datos…</p>
        </div>
        <button id="refresh-btn" type="button" class="btn btn-ghost">
          <svg id="refresh-icon" class="h-4 w-4"><use href="#i-refresh"></use></svg>
          <span class="hidden sm:inline">Actualizar</span>
        </button>
        <button id="theme-toggle" type="button" class="btn btn-ghost btn-icon" aria-label="Cambiar tema" aria-pressed="false">
          <svg class="hidden h-5 w-5 dark:block"><use href="#i-sun"></use></svg>
          <svg class="h-5 w-5 dark:hidden"><use href="#i-moon"></use></svg>
        </button>
      </div>
    </header>

    <main class="px-4 pb-10 pt-5 sm:px-6 xl:px-8">
      <div id="error-banner" class="mb-5 flex hidden items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200" role="alert">
        <svg class="mt-0.5 h-5 w-5 shrink-0"><use href="#i-alert"></use></svg>
        <div class="min-w-0 flex-1">
          <p class="text-sm font-bold">No pudimos cargar el panel</p>
          <p id="error-message" class="mt-0.5 text-xs opacity-90"></p>
        </div>
        <button id="error-retry" type="button" class="shrink-0 rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-bold hover:bg-rose-100 dark:border-rose-400/40 dark:hover:bg-rose-500/10">Reintentar</button>
      </div>

      <section class="card p-4 sm:p-5" aria-labelledby="filters-title">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div class="flex items-center gap-2.5">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-300">
              <svg class="h-4 w-4"><use href="#i-filter"></use></svg>
            </span>
            <div>
              <h2 id="filters-title" class="display text-sm font-bold">Filtros globales</h2>
              <p class="text-xs text-slate-500 dark:text-slate-400">Aplican a KPIs, gráficos y tabla de leads.</p>
            </div>
          </div>
          <div class="flex flex-wrap items-center gap-1.5">
            <span class="chip">
              <svg class="h-3 w-3"><use href="#i-calendar"></use></svg>
              <span id="active-range">Últimos 30 días</span>
            </span>
            <span id="active-days" class="chip">30 días</span>
            <span id="active-monto" class="chip">Todos los montos</span>
          </div>
        </div>

        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-12">
          <div class="xl:col-span-3">
            <label class="field-label" for="preset">Rango de fechas</label>
            <select id="preset" class="field">
              <option value="7d">Últimos 7 días</option>
              <option value="30d" selected>Últimos 30 días</option>
              <option value="90d">Últimos 90 días</option>
              <option value="month">Mes actual</option>
              <option value="prev_month">Mes anterior</option>
              <option value="all">Todo el histórico</option>
              <option value="custom">Personalizado</option>
            </select>
          </div>
          <div class="xl:col-span-2">
            <label class="field-label" for="date-from">Desde</label>
            <input id="date-from" type="date" class="field">
          </div>
          <div class="xl:col-span-2">
            <label class="field-label" for="date-to">Hasta</label>
            <input id="date-to" type="date" class="field">
          </div>
          <div class="xl:col-span-2">
            <label class="field-label" for="monto">Rango de monto</label>
            <select id="monto" class="field">
              <option value="all">Todos los montos</option>
              <option value="hasta_5m">Hasta $5M</option>
              <option value="5m_10m">$5M – $10M</option>
              <option value="10m_20m">$10M – $20M</option>
              <option value="20m_30m">$20M – $30M</option>
              <option value="30m_50m">$30M – $50M</option>
              <option value="mas_50m">Más de $50M</option>
              <option value="sin_monto">Sin monto</option>
            </select>
          </div>
          <div class="flex items-end xl:col-span-3">
            <button id="clear-filters" type="button" class="btn btn-ghost w-full">
              <svg class="h-4 w-4"><use href="#i-x"></use></svg>
              Limpiar filtros
            </button>
          </div>
        </div>
        <p id="date-hint" class="mt-2 hidden text-xs font-medium text-amber-600 dark:text-amber-400">
          Selecciona ambas fechas para aplicar el rango personalizado.
        </p>
      </section>

      <section id="overview-section" class="mt-5" aria-label="Indicadores y visualizaciones">
        <div id="resumen" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <article class="card kpi-card p-5" style="--accent-from:#6366f1;--accent-to:#a5b4fc">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="kpi-label">Leads acumulados</p>
                <p id="kpi-hist" class="kpi-value is-loading">0</p>
              </div>
              <span class="kpi-icon icon-indigo">
                <svg class="h-5 w-5"><use href="#i-inbox"></use></svg>
              </span>
            </div>
            <p id="kpi-hist-sub" class="kpi-sub">—</p>
          </article>

          <article class="card kpi-card p-5" style="--accent-from:#0d9488;--accent-to:#5eead4">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="kpi-label">Leads en período</p>
                <div class="flex flex-wrap items-baseline gap-2">
                  <p id="kpi-period" class="kpi-value is-loading">0</p>
                  <span id="kpi-period-delta" class="delta delta-neutral">—</span>
                </div>
              </div>
              <span class="kpi-icon icon-teal">
                <svg class="h-5 w-5"><use href="#i-trend"></use></svg>
              </span>
            </div>
            <p id="kpi-period-sub" class="kpi-sub">—</p>
          </article>

          <article class="card kpi-card p-5" style="--accent-from:#f59e0b;--accent-to:#fcd34d">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="kpi-label">Leads hoy</p>
                <p id="kpi-today" class="kpi-value is-loading">0</p>
              </div>
              <span class="kpi-icon icon-amber">
                <svg class="h-5 w-5"><use href="#i-calendar-check"></use></svg>
              </span>
            </div>
            <p id="kpi-today-sub" class="kpi-sub">—</p>
          </article>

          <article class="card kpi-card p-5" style="--accent-from:#0ea5e9;--accent-to:#7dd3fc">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="kpi-label">Leads este mes</p>
                <p id="kpi-month" class="kpi-value is-loading">0</p>
              </div>
              <span class="kpi-icon icon-sky">
                <svg class="h-5 w-5"><use href="#i-calendar"></use></svg>
              </span>
            </div>
            <p id="kpi-month-sub" class="kpi-sub">—</p>
          </article>
        </div>

        <div class="mt-5 grid gap-4 xl:grid-cols-3">
          <article id="tendencia" class="card p-5 xl:col-span-2">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Evolución temporal</p>
                <h2 class="display mt-1 text-lg font-extrabold">Tendencia de leads</h2>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Leads y clientes únicos por día o mes.</p>
              </div>
              <div class="segmented" role="group" aria-label="Granularidad de la tendencia">
                <button type="button" data-granularity="day" aria-pressed="true">Diario</button>
                <button type="button" data-granularity="month" aria-pressed="false">Mensual</button>
              </div>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs">
              <span id="trend-total" class="font-semibold text-slate-700 dark:text-slate-200">—</span>
              <span id="trend-average" class="text-slate-500 dark:text-slate-400">—</span>
            </div>
            <div id="trend-shell" class="chart-shell mt-2">
              <div id="trend-chart" role="img" aria-label="Gráfico de tendencia de leads y clientes únicos"></div>
              <div id="trend-skeleton" class="chart-skeleton">
                <div class="chart-skeleton-bar"></div>
              </div>
              <div id="trend-empty" class="empty-state hidden">
                <svg><use href="#i-trend"></use></svg>
                <strong>Sin actividad en el período</strong>
                <p>Amplía el rango de fechas o limpia el filtro de monto para ver la evolución.</p>
              </div>
            </div>
          </article>

          <article id="distribucion" class="card p-5">
            <div>
              <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Composición</p>
              <h2 class="display mt-1 text-lg font-extrabold">Distribución por monto</h2>
              <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Participación de leads según rango.</p>
            </div>
            <div id="dist-shell" class="chart-shell mt-1">
              <div id="dist-chart" role="img" aria-label="Gráfico de distribución de leads por rango de monto"></div>
              <div id="dist-skeleton" class="chart-skeleton">
                <div class="chart-skeleton-bar"></div>
              </div>
              <div id="dist-empty" class="empty-state hidden min-h-64">
                <svg><use href="#i-pie"></use></svg>
                <strong>Sin montos registrados</strong>
                <p>No hay leads que coincidan con los filtros actuales.</p>
              </div>
            </div>
            <ul id="dist-legend" class="mt-2 hidden divide-y divide-slate-100 dark:divide-slate-800"></ul>
            <p id="dist-insight" class="mt-3 rounded-lg bg-slate-50 px-3 py-2 text-xs leading-relaxed text-slate-600 dark:bg-slate-800/50 dark:text-slate-300">—</p>
          </article>
        </div>
      </section>

      <section id="leads" class="card mt-5 overflow-hidden" aria-labelledby="leads-title">
        <div class="border-b border-slate-200/70 p-5 dark:border-slate-800/70">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2.5">
              <div>
                <h2 id="leads-title" class="display text-lg font-extrabold">Leads recientes</h2>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Busca por nombre o ID y revisa el detalle del flujo.</p>
              </div>
              <span id="leads-count" class="chip">Cargando…</span>
            </div>
            <button id="export-btn" type="button" class="btn btn-primary" disabled>
              <svg class="h-4 w-4"><use href="#i-download"></use></svg>
              Exportar CSV
            </button>
          </div>

          <div class="mt-4 flex flex-wrap items-center gap-2">
            <div class="relative w-full sm:w-60">
              <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"><use href="#i-search"></use></svg>
              <input id="search-name" type="search" class="field" style="padding-left:2.25rem" placeholder="Buscar nombre" aria-label="Buscar por nombre">
            </div>
            <div class="w-32">
              <input id="search-id" type="search" class="field" placeholder="ID" aria-label="Filtrar por ID">
            </div>
            <div class="w-28">
              <select id="per-page" class="field" aria-label="Registros por página">
                <option value="10" selected>10 / pág.</option>
                <option value="25">25 / pág.</option>
                <option value="50">50 / pág.</option>
                <option value="100">100 / pág.</option>
              </select>
            </div>
            <button id="clear-table-filters" type="button" class="btn btn-ghost">Limpiar</button>
          </div>
        </div>

        <div id="leads-table-wrap" class="table-wrap">
          <table class="lead-table">
            <thead>
              <tr>
                <th scope="col" aria-sort="none">
                  <button type="button" class="sort-button" data-sort="nombre">
                    Cliente
                    <span class="sort-indicator">↕</span>
                  </button>
                </th>
                <th scope="col">Contacto</th>
                <th scope="col" aria-sort="none">
                  <button type="button" class="sort-button" data-sort="monto">
                    Monto
                    <span class="sort-indicator">↕</span>
                  </button>
                </th>
                <th scope="col" aria-sort="none">
                  <button type="button" class="sort-button" data-sort="judicializado">
                    Judicializado
                    <span class="sort-indicator">↕</span>
                  </button>
                </th>
                <th scope="col" aria-sort="none">
                  <button type="button" class="sort-button" data-sort="ingresos">
                    Ingresos
                    <span class="sort-indicator">↕</span>
                  </button>
                </th>
                <th scope="col" aria-sort="descending">
                  <button type="button" class="sort-button" data-sort="fecha">
                    Fecha
                    <span class="sort-indicator">↓</span>
                  </button>
                </th>
              </tr>
            </thead>
            <tbody id="leads-body">
              <tr>
                <td><div class="flex items-center gap-3"><div class="skeleton h-9 w-9 rounded-xl"></div><div class="space-y-1.5"><div class="skeleton h-3 w-28"></div><div class="skeleton h-2.5 w-20"></div></div></div></td>
                <td><div class="skeleton h-3 w-24"></div></td>
                <td><div class="space-y-1.5"><div class="skeleton h-5 w-24 rounded-md"></div><div class="skeleton h-2.5 w-32"></div></div></td>
                <td><div class="skeleton h-5 w-20 rounded-full"></div></td>
                <td><div class="skeleton h-3 w-20"></div></td>
                <td><div class="space-y-1.5"><div class="skeleton h-3 w-20"></div><div class="skeleton h-2.5 w-10"></div></div></td>
              </tr>
              <tr>
                <td><div class="flex items-center gap-3"><div class="skeleton h-9 w-9 rounded-xl"></div><div class="space-y-1.5"><div class="skeleton h-3 w-24"></div><div class="skeleton h-2.5 w-16"></div></div></div></td>
                <td><div class="skeleton h-3 w-24"></div></td>
                <td><div class="space-y-1.5"><div class="skeleton h-5 w-20 rounded-md"></div><div class="skeleton h-2.5 w-28"></div></div></td>
                <td><div class="skeleton h-5 w-24 rounded-full"></div></td>
                <td><div class="skeleton h-3 w-24"></div></td>
                <td><div class="space-y-1.5"><div class="skeleton h-3 w-20"></div><div class="skeleton h-2.5 w-10"></div></div></td>
              </tr>
              <tr>
                <td><div class="flex items-center gap-3"><div class="skeleton h-9 w-9 rounded-xl"></div><div class="space-y-1.5"><div class="skeleton h-3 w-32"></div><div class="skeleton h-2.5 w-20"></div></div></div></td>
                <td><div class="skeleton h-3 w-24"></div></td>
                <td><div class="space-y-1.5"><div class="skeleton h-5 w-24 rounded-md"></div><div class="skeleton h-2.5 w-32"></div></div></td>
                <td><div class="skeleton h-5 w-20 rounded-full"></div></td>
                <td><div class="skeleton h-3 w-16"></div></td>
                <td><div class="space-y-1.5"><div class="skeleton h-3 w-20"></div><div class="skeleton h-2.5 w-10"></div></div></td>
              </tr>
              <tr>
                <td><div class="flex items-center gap-3"><div class="skeleton h-9 w-9 rounded-xl"></div><div class="space-y-1.5"><div class="skeleton h-3 w-24"></div><div class="skeleton h-2.5 w-20"></div></div></div></td>
                <td><div class="skeleton h-3 w-24"></div></td>
                <td><div class="space-y-1.5"><div class="skeleton h-5 w-20 rounded-md"></div><div class="skeleton h-2.5 w-24"></div></div></td>
                <td><div class="skeleton h-5 w-24 rounded-full"></div></td>
                <td><div class="skeleton h-3 w-20"></div></td>
                <td><div class="space-y-1.5"><div class="skeleton h-3 w-20"></div><div class="skeleton h-2.5 w-10"></div></div></td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-200/70 p-4 dark:border-slate-800/70 sm:flex-row sm:items-center sm:justify-between">
          <p id="pagination-info" class="text-xs text-slate-500 dark:text-slate-400">Cargando…</p>
          <div class="flex items-center gap-1.5">
            <button id="pagination-prev" type="button" class="page-btn" aria-label="Página anterior" disabled>
              <svg class="h-4 w-4"><use href="#i-chevron-left"></use></svg>
            </button>
            <div id="pagination-pages" class="flex items-center gap-1"></div>
            <button id="pagination-next" type="button" class="page-btn" aria-label="Página siguiente" disabled>
              <svg class="h-4 w-4"><use href="#i-chevron-right"></use></svg>
            </button>
          </div>
        </div>
      </section>

      <footer class="mt-6 flex flex-col gap-1 px-1 text-[11px] text-slate-400 dark:text-slate-500 sm:flex-row sm:items-center sm:justify-between">
        <p>Totemly · Deuda Protegida · Panel analítico de leads</p>
        <p>Fuente: MySQL <span class="mono">totemly</span></p>
      </footer>
    </main>
  </div>

  <div id="toast-container" class="fixed bottom-4 right-4 z-50 flex flex-col items-end gap-2" aria-live="polite" aria-atomic="false"></div>

  <div class="pointer-events-none fixed h-0 w-0 overflow-hidden opacity-0" aria-hidden="true">
    <span class="flex items-center gap-3 min-w-0 truncate font-semibold text-slate-900 dark:text-slate-100 mono mt-0.5 text-xs text-slate-500 dark:text-slate-400 inline-flex gap-1.5 font-medium text-emerald-600 hover:text-emerald-500 dark:text-emerald-400 dark:hover:text-emerald-300 h-3.5 w-3.5 whitespace-nowrap max-w-44 text-slate-400 dark:text-slate-500 flex-col text-slate-700 dark:text-slate-200 mt-2 text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 h-4 w-4 shrink-0 flex-1 text-center"></span>
  </div>

  <script src="assets/vendor/apexcharts.min.js"></script>
  <script type="module" src="assets/js/app.js"></script>
</body>
</html>
