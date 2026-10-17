import { buildExportUrl, fetchLeads, fetchOverview } from "./api.js";
import {
  avatarGradient,
  debounce,
  formatDate,
  formatDateRange,
  formatMonthLong,
  formatNumber,
  formatPercent,
  formatPhone,
  formatRut,
  formatTime,
  formatWeekday,
  initials,
  statusLabel,
  statusTone,
  whatsappLink,
} from "./format.js";
import {
  BUCKET_COLORS,
  renderDistributionChart,
  renderTrendChart,
} from "./charts.js";

const NS = "http://www.w3.org/2000/svg";

const state = {
  overview: null,
  leads: null,
  filters: {
    preset: "30d",
    from: null,
    to: null,
    all: false,
    monto: "all",
  },
  granularity: "auto",
  table: {
    q: "",
    rut: "",
    page: 1,
    perPage: 10,
    sort: "fecha",
    dir: "desc",
  },
};

let overviewToken = 0;
let leadsToken = 0;

const $ = (id) => document.getElementById(id);

function el(tag, className, text) {
  const node = document.createElement(tag);
  if (className) node.className = className;
  if (text !== undefined) node.textContent = text;
  return node;
}

function icon(name, className = "h-4 w-4") {
  const svg = document.createElementNS(NS, "svg");
  svg.setAttribute("class", className);
  svg.setAttribute("aria-hidden", "true");
  const use = document.createElementNS(NS, "use");
  use.setAttribute("href", `#i-${name}`);
  svg.appendChild(use);
  return svg;
}

function setText(id, value) {
  const node = $(id);
  if (node) node.textContent = value;
}

function capitalize(value) {
  return value ? value.charAt(0).toUpperCase() + value.slice(1) : value;
}

function countLabel(value, singular, plural) {
  const number = Number(value) || 0;
  return `${formatNumber(number)} ${number === 1 ? singular : plural}`;
}

function pad(value) {
  return String(value).padStart(2, "0");
}

function toISODate(date) {
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

function startOfDay(date = new Date()) {
  return new Date(date.getFullYear(), date.getMonth(), date.getDate(), 12, 0, 0);
}

function addDays(date, days) {
  const next = new Date(date);
  next.setDate(next.getDate() + days);
  return next;
}

function presetRange(key) {
  const today = startOfDay(new Date());
  const year = today.getFullYear();
  const month = today.getMonth();
  const range = (from, to) => ({ from: toISODate(from), to: toISODate(to), all: false });

  switch (key) {
    case "7d":
      return range(addDays(today, -6), today);
    case "30d":
      return range(addDays(today, -29), today);
    case "90d":
      return range(addDays(today, -89), today);
    case "month":
      return range(new Date(year, month, 1, 12), today);
    case "prev_month":
      return range(new Date(year, month - 1, 1, 12), new Date(year, month, 0, 12));
    case "all":
      return { from: null, to: null, all: true };
    default:
      return null;
  }
}

function isDark() {
  return document.documentElement.classList.contains("dark");
}

function canQuery() {
  return state.filters.all || Boolean(state.filters.from && state.filters.to);
}

function rangeParams() {
  const params = { monto: state.filters.monto };
  if (state.filters.all) {
    params.all = 1;
  } else if (state.filters.from && state.filters.to) {
    params.from = state.filters.from;
    params.to = state.filters.to;
  }
  return params;
}

function overviewParams() {
  return {
    ...rangeParams(),
    granularity: state.granularity,
  };
}

function leadsParams() {
  return {
    ...rangeParams(),
    page: state.table.page,
    per_page: state.table.perPage,
    q: state.table.q,
    rut: state.table.rut,
    sort: state.table.sort,
    dir: state.table.dir,
  };
}

function initDates() {
  const preset = presetRange("30d");
  state.filters = {
    ...state.filters,
    preset: "30d",
    from: preset.from,
    to: preset.to,
    all: false,
  };
  $("preset").value = "30d";
  $("date-from").value = preset.from;
  $("date-to").value = preset.to;
  $("date-hint").classList.add("hidden");
  updateDateConstraints();
  updateActiveFilterChip();
}

function updateDateConstraints() {
  const from = $("date-from");
  const to = $("date-to");
  const today = toISODate(startOfDay(new Date()));
  from.max = to.value || today;
  to.min = from.value || "";
  to.max = today;
}

function setPreset(preset, { fetch = true } = {}) {
  const range = presetRange(preset);
  if (!range) return;

  state.filters.preset = preset;
  state.filters.from = range.from;
  state.filters.to = range.to;
  state.filters.all = Boolean(range.all);
  state.table.page = 1;

  $("preset").value = preset;
  $("date-from").value = range.from ?? "";
  $("date-to").value = range.to ?? "";
  $("date-hint").classList.add("hidden");
  updateDateConstraints();

  if (fetch) {
    loadOverview();
    loadLeads();
  }
}

function updateActiveFilterChip() {
  if (state.overview) {
    const range = state.overview.range;
    setText("active-range", range.mode === "all" ? "Histórico completo" : formatDateRange(range.from, range.to));
    setText("active-days", `${formatNumber(range.days)} ${range.days === 1 ? "día" : "días"}`);
  }

  const selected = $("monto").selectedOptions[0];
  setText("active-monto", selected ? selected.textContent.trim() : "Todos los montos");
}

function setOverviewLoading(busy) {
  const initial = !state.overview;
  ["kpi-hist", "kpi-period", "kpi-today", "kpi-month"].forEach((id) => {
    $(id).classList.toggle("is-loading", busy && initial);
  });

  $("trend-shell").classList.toggle("is-loading", busy && !initial);
  $("dist-shell").classList.toggle("is-loading", busy && !initial);
  $("trend-skeleton").classList.toggle("hidden", !(busy && initial));
  $("dist-skeleton").classList.toggle("hidden", !(busy && initial));
  $("overview-section").setAttribute("aria-busy", String(busy));
  $("refresh-icon").classList.toggle("spin", busy);
}

function setLeadsLoading(busy) {
  const initial = !state.leads;
  $("leads-table-wrap").classList.toggle("is-loading", busy && !initial);
  $("leads").setAttribute("aria-busy", String(busy));
  if (busy && initial) {
    setText("leads-count", "Cargando…");
  }
}

function setDbStatus(online) {
  const dot = $("db-dot");
  dot.classList.toggle("is-online", online);
  dot.classList.toggle("is-offline", !online);
  setText("db-status", online ? "MySQL conectado" : "Sin conexión");
}

function showError(message) {
  setText("error-message", message);
  $("error-banner").classList.remove("hidden");
}

function hideError() {
  $("error-banner").classList.add("hidden");
}

function toast(message, type = "info") {
  const container = $("toast-container");
  const node = el("div", `toast ${type === "error" ? "is-error" : type === "success" ? "is-success" : ""}`);
  node.setAttribute("role", "status");
  node.appendChild(icon(type === "error" ? "alert" : "check", "h-4 w-4 mt-0.5 shrink-0"));
  node.appendChild(el("p", "flex-1", message));
  container.appendChild(node);
  window.setTimeout(() => node.remove(), 4600);
}

async function loadOverview() {
  if (!canQuery()) return;
  const token = ++overviewToken;
  setOverviewLoading(true);

  try {
    const data = await fetchOverview(overviewParams());
    if (token !== overviewToken) return;
    state.overview = data;
    renderOverview(data);
    hideError();
    setDbStatus(true);
    setText("last-updated", `Actualizado ${formatTime()}`);
    setText("sidebar-updated", `Actualizado ${formatTime()}`);
  } catch (error) {
    if (token !== overviewToken) return;
    showError(error.message);
    if (error.status === 0 || error.status >= 500) setDbStatus(false);
  } finally {
    if (token === overviewToken) {
      setOverviewLoading(false);
    }
  }
}

async function loadLeads() {
  if (!canQuery()) return;
  const token = ++leadsToken;
  setLeadsLoading(true);

  try {
    const data = await fetchLeads(leadsParams());
    if (token !== leadsToken) return;
    state.leads = data;
    state.table.page = data.meta.page;
    renderLeads(data);
  } catch (error) {
    if (token !== leadsToken) return;
    toast(error.message, "error");
    if (!state.leads) {
      $("leads-body").replaceChildren(renderTableError(error.message));
      setText("leads-count", "Error");
      setText("pagination-info", "No se pudieron cargar los leads");
      $("export-btn").disabled = true;
    }
    if (error.status === 0 || error.status >= 500) setDbStatus(false);
  } finally {
    if (token === leadsToken) {
      setLeadsLoading(false);
    }
  }
}

function refreshAll() {
  if (!canQuery()) {
    toast("Selecciona un rango de fechas completo para aplicar los filtros.", "error");
    return;
  }
  loadOverview();
  loadLeads();
}

function renderOverview(data) {
  const kpis = data.kpis;

  setText("kpi-hist", formatNumber(kpis.total_historico));
  setText(
    "kpi-hist-sub",
    `${countLabel(kpis.clientes_historicos, "cliente único", "clientes únicos")} · ${countLabel(
      kpis.periodo,
      "lead",
      "leads"
    )} en el período`
  );

  setText("kpi-period", formatNumber(kpis.periodo));
  setText(
    "kpi-period-sub",
    `${countLabel(kpis.clientes_periodo, "cliente único", "clientes únicos")} en el período`
  );
  renderDelta(kpis);

  setText("kpi-today", formatNumber(kpis.hoy));
  setText(
    "kpi-today-sub",
    capitalize(new Date().toLocaleDateString("es-CL", { weekday: "long", day: "numeric", month: "long" }))
  );

  setText("kpi-month", formatNumber(kpis.mes));
  setText("kpi-month-sub", capitalize(formatMonthLong(new Date())));

  setText("trend-total", `${countLabel(data.trend.total, "lead", "leads")} en el período`);
  setText("trend-average", `Promedio ${formatNumber(kpis.promedio_diario)} / día`);
  setText(
    "dist-insight",
    kpis.monto_principal
      ? `Rango principal: ${kpis.monto_principal.label} · ${formatPercent(kpis.monto_principal.pct)} del total`
      : "Sin datos para el período seleccionado."
  );

  updateActiveFilterChip();
  updateGranularityButtons(data.trend.granularity);
  renderCharts();
}

function renderDelta(kpis) {
  const node = $("kpi-period-delta");
  node.className = "delta delta-neutral";

  if (!kpis.comparison_available) {
    node.textContent = "Histórico";
    return;
  }

  if (kpis.periodo_anterior === 0) {
    node.textContent = kpis.periodo > 0 ? "Nuevo" : "Sin registros";
    return;
  }

  const variation = Number(kpis.variacion_pct ?? 0);
  if (variation > 0) {
    node.classList.remove("delta-neutral");
    node.classList.add("delta-positive");
    node.textContent = `+${formatPercent(variation)}`;
  } else if (variation < 0) {
    node.classList.remove("delta-neutral");
    node.classList.add("delta-negative");
    node.textContent = formatPercent(variation);
  } else {
    node.textContent = "0%";
  }
}

function updateGranularityButtons(active) {
  document.querySelectorAll("[data-granularity]").forEach((button) => {
    button.setAttribute("aria-pressed", String(button.dataset.granularity === active));
  });
}

function renderCharts() {
  if (!state.overview) return;

  const { trend, distribution } = state.overview;
  const hasTrend = trend.series.some((point) => point.total > 0);
  const trendElement = $("trend-chart");
  trendElement.classList.toggle("hidden", !hasTrend);
  $("trend-empty").classList.toggle("hidden", hasTrend);

  if (hasTrend) {
    renderTrendChart(trendElement, trend.series, trend.granularity, isDark());
  } else {
    renderTrendChart(trendElement, [], trend.granularity, isDark());
  }

  const hasDistribution = distribution.length > 0;
  const distElement = $("dist-chart");
  distElement.classList.toggle("hidden", !hasDistribution);
  $("dist-empty").classList.toggle("hidden", hasDistribution);
  $("dist-legend").classList.toggle("hidden", !hasDistribution);

  if (hasDistribution) {
    renderDistributionChart(distElement, distribution, isDark());
  } else {
    renderDistributionChart(distElement, [], isDark());
  }

  renderDistributionLegend(distribution);
}

function renderDistributionLegend(distribution) {
  const list = $("dist-legend");
  list.replaceChildren();

  distribution.forEach((item) => {
    const row = el("li", "legend-item");
    const dot = el("span", "legend-dot");
    dot.style.backgroundColor = BUCKET_COLORS[item.key] || "#6366f1";
    row.appendChild(dot);
    row.appendChild(el("span", "legend-label", item.label));
    row.appendChild(el("span", "legend-value", formatNumber(item.total)));
    row.appendChild(el("span", "legend-pct", formatPercent(item.pct)));
    list.appendChild(row);
  });
}

function renderLeads(data) {
  const { rows, meta } = data;
  const body = $("leads-body");
  body.replaceChildren();

  setText("leads-count", `${formatNumber(meta.total)} ${meta.total === 1 ? "lead" : "leads"}`);
  $("export-btn").disabled = meta.total === 0;

  if (rows.length === 0) {
    body.appendChild(renderEmptyRow(meta));
  } else {
    const fragment = document.createDocumentFragment();
    rows.forEach((lead) => fragment.appendChild(renderLeadRow(lead)));
    body.appendChild(fragment);
  }

  updateSortIndicators(meta.sort, meta.dir);
  renderPagination(meta);
  setText(
    "pagination-info",
    meta.total === 0
      ? "Sin resultados"
      : `Mostrando ${formatNumber((meta.page - 1) * meta.per_page + 1)}–${formatNumber(
          Math.min(meta.page * meta.per_page, meta.total)
        )} de ${formatNumber(meta.total)}`
  );
}

function renderTableError(message) {
  const row = el("tr");
  const cell = el("td", "text-center");
  cell.colSpan = 5;
  const wrapper = el("div", "empty-state");
  wrapper.appendChild(icon("alert", ""));
  wrapper.appendChild(el("strong", "", "No pudimos cargar los leads"));
  wrapper.appendChild(el("p", "", message || "Revisa la conexión con MySQL e intenta nuevamente."));
  const retry = el("button", "mt-2 text-xs font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400", "Reintentar");
  retry.type = "button";
  retry.addEventListener("click", () => loadLeads());
  wrapper.appendChild(retry);
  cell.appendChild(wrapper);
  row.appendChild(cell);
  return row;
}

function renderEmptyRow(meta) {
  const row = el("tr");
  const cell = el("td", "text-center");
  cell.colSpan = 5;
  const wrapper = el("div", "empty-state");
  wrapper.appendChild(icon("search", ""));
  const hasFilters = Boolean(state.table.q || state.table.rut);
  wrapper.appendChild(el("strong", "", hasFilters ? "No encontramos leads" : "Sin leads en el período"));
  wrapper.appendChild(
    el(
      "p",
      "",
      hasFilters
        ? "Prueba con otro nombre, RUT o amplía el rango de fechas."
        : "Ajusta los filtros globales o selecciona otro período para ver resultados."
    )
  );
  if (hasFilters) {
    const clear = el("button", "mt-2 text-xs font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400", "Limpiar búsqueda");
    clear.type = "button";
    clear.addEventListener("click", clearTableFilters);
    wrapper.appendChild(clear);
  }
  cell.appendChild(wrapper);
  row.appendChild(cell);
  return row;
}

function renderLeadRow(lead) {
  const row = el("tr");

  const clientCell = el("td");
  const client = el("div", "flex items-center gap-3");
  const avatar = el("span", "avatar", initials(lead.nombre));
  avatar.style.background = avatarGradient(lead.id);
  const clientText = el("div", "min-w-0");
  const name = el("p", "truncate font-semibold text-slate-900 dark:text-slate-100", lead.nombre || "Sin nombre");
  name.title = lead.nombre || "Sin nombre";
  clientText.appendChild(name);
  clientText.appendChild(el("p", "mono mt-0.5 text-xs text-slate-500 dark:text-slate-400", formatRut(lead.id)));
  client.appendChild(avatar);
  client.appendChild(clientText);
  clientCell.appendChild(client);
  row.appendChild(clientCell);

  const contactCell = el("td");
  const wa = whatsappLink(lead.numero);
  if (wa) {
    const link = el("a", "inline-flex items-center gap-1.5 font-medium text-emerald-600 hover:text-emerald-500 dark:text-emerald-400 dark:hover:text-emerald-300");
    link.href = wa;
    link.target = "_blank";
    link.rel = "noopener noreferrer";
    link.title = `Abrir WhatsApp de ${lead.nombre || "este lead"}`;
    link.appendChild(icon("whatsapp", "h-3.5 w-3.5"));
    link.appendChild(el("span", "whitespace-nowrap", formatPhone(lead.numero)));
    contactCell.appendChild(link);
  } else {
    contactCell.appendChild(el("span", "text-slate-400", "—"));
  }
  row.appendChild(contactCell);

  const montoCell = el("td");
  const badge = el("span", "monto-badge", lead.monto_bucket_label);
  badge.style.setProperty("--dot", BUCKET_COLORS[lead.monto_bucket] || "#6366f1");
  const rawMonto = el("p", "mt-1 max-w-44 truncate text-xs text-slate-400 dark:text-slate-500", lead.monto || "—");
  rawMonto.title = lead.monto || "Sin monto";
  montoCell.appendChild(badge);
  montoCell.appendChild(rawMonto);
  row.appendChild(montoCell);

  const statusCell = el("td");
  const status = el("span", `status-badge ${statusTone(lead.otro)}`);
  status.title = statusLabel(lead.otro);
  status.appendChild(el("span", "status-dot"));
  status.appendChild(el("span", "", statusLabel(lead.otro)));
  statusCell.appendChild(status);
  row.appendChild(statusCell);

  const dateCell = el("td");
  const date = el("div", "flex flex-col");
  date.appendChild(el("span", "font-medium text-slate-700 dark:text-slate-200", formatDate(lead.fecha)));
  date.appendChild(el("span", "text-xs text-slate-400 dark:text-slate-500", formatWeekday(lead.fecha)));
  dateCell.appendChild(date);
  row.appendChild(dateCell);

  return row;
}

function updateSortIndicators(sort, dir) {
  document.querySelectorAll("[data-sort]").forEach((button) => {
    const active = button.dataset.sort === sort;
    button.dataset.active = String(active);
    button.closest("th").setAttribute("aria-sort", active ? (dir === "asc" ? "ascending" : "descending") : "none");
    const indicator = button.querySelector(".sort-indicator");
    if (indicator) indicator.textContent = active ? (dir === "asc" ? "↑" : "↓") : "↕";
  });
}

function renderPagination(meta) {
  const pagesNode = $("pagination-pages");
  pagesNode.replaceChildren();

  const totalPages = meta.pages;
  $("pagination-prev").disabled = totalPages === 0 || meta.page <= 1;
  $("pagination-next").disabled = totalPages === 0 || meta.page >= totalPages;

  if (totalPages === 0) return;

  pageList(meta.page, totalPages).forEach((item) => {
    if (item === "…") {
      pagesNode.appendChild(el("span", "page-ellipsis", "…"));
      return;
    }
    const button = el("button", `page-btn${item === meta.page ? " is-active" : ""}`, String(item));
    button.type = "button";
    button.setAttribute("aria-label", `Página ${item}`);
    if (item === meta.page) button.setAttribute("aria-current", "page");
    button.addEventListener("click", () => {
      if (item === state.table.page) return;
      state.table.page = item;
      loadLeads();
    });
    pagesNode.appendChild(button);
  });
}

function pageList(current, total) {
  if (total <= 7) {
    return Array.from({ length: total }, (_, index) => index + 1);
  }

  if (current <= 4) {
    return [1, 2, 3, 4, 5, "…", total];
  }

  if (current >= total - 3) {
    return [1, "…", total - 4, total - 3, total - 2, total - 1, total];
  }

  return [1, "…", current - 1, current, current + 1, "…", total];
}

function clearTableFilters() {
  state.table.q = "";
  state.table.rut = "";
  state.table.page = 1;
  $("search-name").value = "";
  $("search-rut").value = "";
  loadLeads();
}

function clearAllFilters() {
  state.granularity = "auto";
  state.table = {
    q: "",
    rut: "",
    page: 1,
    perPage: 10,
    sort: "fecha",
    dir: "desc",
  };
  $("monto").value = "all";
  $("search-name").value = "";
  $("search-rut").value = "";
  $("per-page").value = "10";
  updateGranularityButtons("day");
  setPreset("30d", { fetch: false });
  loadOverview();
  loadLeads();
}

function exportCsv() {
  if (!state.leads || state.leads.meta.total === 0) {
    toast("No hay leads para exportar con los filtros actuales.", "error");
    return;
  }
  const params = {
    ...rangeParams(),
    q: state.table.q,
    rut: state.table.rut,
  };
  window.location.href = buildExportUrl(params);
}

function syncThemeControls() {
  const dark = isDark();
  const toggle = $("theme-toggle");
  toggle.setAttribute("aria-pressed", String(dark));
  toggle.setAttribute("aria-label", dark ? "Cambiar a tema claro" : "Cambiar a tema oscuro");
}

function toggleTheme() {
  const dark = !isDark();
  document.documentElement.classList.toggle("dark", dark);
  try {
    localStorage.setItem("dp-theme", dark ? "dark" : "light");
  } catch {
    localStorage.removeItem("dp-theme");
  }
  syncThemeControls();
  window.requestAnimationFrame(() => renderCharts());
}

function toggleSidebar(open) {
  const sidebar = $("sidebar");
  const overlay = $("sidebar-overlay");
  sidebar.classList.toggle("-translate-x-full", !open);
  overlay.classList.toggle("hidden", !open);
  $("menu-btn").setAttribute("aria-expanded", String(open));
  document.body.classList.toggle("overflow-hidden", open && window.innerWidth < 1024);
}

function bindEvents() {
  $("preset").addEventListener("change", (event) => {
    const value = event.target.value;
    if (value === "custom") {
      $("date-from").focus();
      return;
    }
    setPreset(value);
  });

  ["date-from", "date-to"].forEach((id) => {
    $(id).addEventListener("change", () => {
      const from = $("date-from").value || null;
      const to = $("date-to").value || null;
      state.filters.from = from;
      state.filters.to = to;
      state.filters.all = false;
      state.filters.preset = "custom";
      $("preset").value = "custom";
      updateDateConstraints();

      if (!from || !to) {
        $("date-hint").classList.remove("hidden");
        return;
      }
      $("date-hint").classList.add("hidden");
      if (from > to) {
        toast("La fecha desde no puede ser mayor que la fecha hasta.", "error");
        return;
      }
      state.table.page = 1;
      loadOverview();
      loadLeads();
    });
  });

  $("monto").addEventListener("change", (event) => {
    state.filters.monto = event.target.value;
    state.table.page = 1;
    updateActiveFilterChip();
    loadOverview();
    loadLeads();
  });

  $("clear-filters").addEventListener("click", clearAllFilters);
  $("refresh-btn").addEventListener("click", refreshAll);
  $("error-retry").addEventListener("click", refreshAll);
  $("theme-toggle").addEventListener("click", toggleTheme);

  document.querySelectorAll("[data-granularity]").forEach((button) => {
    button.addEventListener("click", () => {
      state.granularity = button.dataset.granularity;
      updateGranularityButtons(state.granularity);
      loadOverview();
    });
  });

  document.querySelectorAll("[data-sort]").forEach((button) => {
    button.addEventListener("click", () => {
      const sort = button.dataset.sort;
      if (state.table.sort === sort) {
        state.table.dir = state.table.dir === "asc" ? "desc" : "asc";
      } else {
        state.table.sort = sort;
        state.table.dir = sort === "fecha" ? "desc" : "asc";
      }
      state.table.page = 1;
      loadLeads();
    });
  });

  const debouncedName = debounce(() => {
    state.table.q = $("search-name").value.trim();
    state.table.page = 1;
    loadLeads();
  }, 350);

  const debouncedRut = debounce(() => {
    state.table.rut = $("search-rut").value.trim();
    state.table.page = 1;
    loadLeads();
  }, 350);

  $("search-name").addEventListener("input", debouncedName);
  $("search-rut").addEventListener("input", debouncedRut);
  $("per-page").addEventListener("change", (event) => {
    state.table.perPage = Number(event.target.value);
    state.table.page = 1;
    loadLeads();
  });

  $("pagination-prev").addEventListener("click", () => {
    if (state.table.page > 1) {
      state.table.page -= 1;
      loadLeads();
    }
  });

  $("pagination-next").addEventListener("click", () => {
    const pages = state.leads?.meta.pages ?? 0;
    if (state.table.page < pages) {
      state.table.page += 1;
      loadLeads();
    }
  });

  $("clear-table-filters").addEventListener("click", clearTableFilters);
  $("export-btn").addEventListener("click", exportCsv);

  $("menu-btn").addEventListener("click", () => toggleSidebar(true));
  $("sidebar-close").addEventListener("click", () => toggleSidebar(false));
  $("sidebar-overlay").addEventListener("click", () => toggleSidebar(false));

  document.querySelectorAll(".sidebar-link").forEach((link) => {
    link.addEventListener("click", () => {
      if (window.innerWidth < 1024) toggleSidebar(false);
    });
  });

  window.addEventListener("keydown", (event) => {
    if (event.key === "Escape") toggleSidebar(false);
  });

  window.addEventListener("resize", () => {
    if (window.innerWidth >= 1024) {
      $("sidebar-overlay").classList.add("hidden");
      document.body.classList.remove("overflow-hidden");
    }
  });
}

function init() {
  initDates();
  syncThemeControls();
  bindEvents();
  updateGranularityButtons("day");
  setText("last-updated", "Cargando datos…");
  setText("sidebar-updated", "Conectando…");
  setDbStatus(false);
  refreshAll();
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", init, { once: true });
} else {
  init();
}
