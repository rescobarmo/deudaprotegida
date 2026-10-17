const numberFormatter = new Intl.NumberFormat("es-CL");
const percentFormatter = new Intl.NumberFormat("es-CL", { maximumFractionDigits: 1 });
const currencyFormatter = new Intl.NumberFormat("es-CL", {
  style: "currency",
  currency: "CLP",
  maximumFractionDigits: 0,
});

const MONTHS_SHORT = ["ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"];
const MONTHS_LONG = [
  "enero",
  "febrero",
  "marzo",
  "abril",
  "mayo",
  "junio",
  "julio",
  "agosto",
  "septiembre",
  "octubre",
  "noviembre",
  "diciembre",
];
const WEEKDAYS_SHORT = ["dom", "lun", "mar", "mié", "jue", "vie", "sáb"];

export function parseDate(iso) {
  if (!iso || typeof iso !== "string") return null;
  const match = iso.match(/^(\d{4})-(\d{2})-(\d{2})/);
  if (!match) return null;
  const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]), 12, 0, 0);
  return Number.isNaN(date.getTime()) ? null : date;
}

export function formatNumber(value) {
  const number = Number(value);
  return Number.isFinite(number) ? numberFormatter.format(number) : "—";
}

export function formatPercent(value) {
  const number = Number(value);
  return Number.isFinite(number) ? `${percentFormatter.format(number)}%` : "—";
}

export function formatCurrency(value) {
  const number = Number(value);
  return Number.isFinite(number) ? currencyFormatter.format(number) : "—";
}

export function compactNumber(value) {
  const number = Number(value);
  if (!Number.isFinite(number)) return "—";
  if (Math.abs(number) >= 1000000) return `${(number / 1000000).toFixed(number % 1000000 === 0 ? 0 : 1)}M`;
  if (Math.abs(number) >= 1000) return `${(number / 1000).toFixed(number % 1000 === 0 ? 0 : 1)}K`;
  return numberFormatter.format(number);
}

export function formatDate(iso, options = {}) {
  const date = parseDate(iso);
  if (!date) return "—";
  return date.toLocaleDateString("es-CL", {
    day: "2-digit",
    month: "short",
    year: "numeric",
    ...options,
  });
}

export function formatDayShort(iso) {
  const date = parseDate(iso);
  if (!date) return "—";
  return `${String(date.getDate()).padStart(2, "0")} ${MONTHS_SHORT[date.getMonth()]}`;
}

export function formatMonthShort(iso) {
  const date = parseDate(iso);
  if (!date) return "—";
  return `${MONTHS_SHORT[date.getMonth()]} ${String(date.getFullYear()).slice(2)}`;
}

export function formatMonthLong(date = new Date()) {
  return `${MONTHS_LONG[date.getMonth()]} ${date.getFullYear()}`;
}

export function formatWeekday(iso) {
  const date = parseDate(iso);
  if (!date) return "";
  return WEEKDAYS_SHORT[date.getDay()];
}

export function formatDateRange(from, to) {
  const start = parseDate(from);
  const end = parseDate(to);
  if (!start || !end) return "Período no definido";
  if (from === to) return formatDate(from);
  const sameYear = start.getFullYear() === end.getFullYear();
  const startLabel = start.toLocaleDateString("es-CL", {
    day: "2-digit",
    month: "short",
    ...(sameYear ? {} : { year: "numeric" }),
  });
  return `${startLabel} – ${formatDate(to)}`;
}

export function formatTime(date = new Date()) {
  return date.toLocaleTimeString("es-CL", { hour: "2-digit", minute: "2-digit" });
}

export function formatRut(raw) {
  const source = String(raw ?? "").trim();
  if (!source) return "—";
  const cleaned = source.replace(/\./g, "").replace(/-/g, "").replace(/\s+/g, "");
  const match = cleaned.match(/^(\d{1,2})(\d{3})(\d{3})([0-9kK])$/);
  if (!match) return source;
  return `${match[1]}.${match[2]}.${match[3]}-${match[4].toUpperCase()}`;
}

export function normalizePhone(raw) {
  return String(raw ?? "").replace(/\D/g, "");
}

export function formatPhone(raw) {
  const digits = normalizePhone(raw);
  if (!digits) return "—";
  if (digits.length === 11 && digits.startsWith("56")) {
    const local = digits.slice(2);
    return `+56 ${local.slice(0, 1)} ${local.slice(1, 5)} ${local.slice(5)}`;
  }
  if (digits.length === 9) {
    return `+56 ${digits.slice(0, 1)} ${digits.slice(1, 5)} ${digits.slice(5)}`;
  }
  if (digits.length === 8) {
    return `+56 ${digits.slice(0, 4)} ${digits.slice(4)}`;
  }
  return String(raw ?? "").trim();
}

export function whatsappLink(raw) {
  let digits = normalizePhone(raw);
  if (!digits) return null;
  if (digits.length === 9 && digits.startsWith("9")) digits = `56${digits}`;
  if (digits.length === 8) digits = `56${digits}`;
  return /^\d{8,15}$/.test(digits) ? `https://wa.me/${digits}` : null;
}

export function statusTone(value) {
  const text = String(value ?? "").trim();
  if (!text) return "status-neutral";
  if (/aprob|activo|vigente|complet|exitos|ganad|^si$|^1$/i.test(text)) return "status-positive";
  if (/rechaz|inactiv|anulad|perdid|error|^no$|^0$/i.test(text)) return "status-negative";
  if (/pend|espera|revis|proceso|gestion|contact/i.test(text)) return "status-warning";
  return "status-neutral";
}

export function statusLabel(value) {
  const text = String(value ?? "").trim();
  return text || "Sin estado";
}

export function initials(name) {
  const source = String(name ?? "").trim();
  if (!source) return "?";
  const base = source.includes("@") ? source.split("@")[0] : source;
  const parts = base.split(/[\s._-]+/).filter(Boolean);
  if (parts.length >= 2) {
    return `${parts[0][0]}${parts[1][0]}`.toUpperCase();
  }
  return base.slice(0, 2).toUpperCase();
}

const AVATAR_GRADIENTS = [
  "linear-gradient(135deg, #6366f1, #4338ca)",
  "linear-gradient(135deg, #0d9488, #0f766e)",
  "linear-gradient(135deg, #f59e0b, #b45309)",
  "linear-gradient(135deg, #0ea5e9, #0369a1)",
  "linear-gradient(135deg, #ec4899, #be185d)",
  "linear-gradient(135deg, #8b5cf6, #6d28d9)",
];

export function avatarGradient(seed) {
  const text = String(seed ?? "");
  let hash = 0;
  for (let index = 0; index < text.length; index += 1) {
    hash = (hash * 31 + text.charCodeAt(index)) >>> 0;
  }
  return AVATAR_GRADIENTS[hash % AVATAR_GRADIENTS.length];
}

export function debounce(fn, delay = 300) {
  let timer = null;
  const debounced = (...args) => {
    if (timer) window.clearTimeout(timer);
    timer = window.setTimeout(() => {
      timer = null;
      fn(...args);
    }, delay);
  };
  debounced.cancel = () => {
    if (timer) window.clearTimeout(timer);
    timer = null;
  };
  return debounced;
}


