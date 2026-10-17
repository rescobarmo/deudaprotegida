const API_BASE = new URL("api/", document.baseURI);

export class ApiError extends Error {
  constructor(message, status = 0) {
    super(message);
    this.name = "ApiError";
    this.status = status;
  }
}

function toUrl(endpoint, params = {}) {
  const url = new URL(endpoint, API_BASE);
  Object.entries(params).forEach(([key, value]) => {
    if (value === null || value === undefined || value === "") return;
    url.searchParams.set(key, String(value));
  });
  return url;
}

async function request(endpoint, params = {}) {
  const url = toUrl(endpoint, params);
  let response;
  try {
    response = await fetch(url, {
      method: "GET",
      cache: "no-store",
      headers: { Accept: "application/json" },
    });
  } catch {
    throw new ApiError("No se pudo contactar con el servidor. Verifica Apache y MySQL.", 0);
  }

  if (response.status === 401) {
    const loginUrl = new URL("login.php", document.baseURI);
    window.location.replace(loginUrl.toString());
    throw new ApiError("Sesión expirada. Redirigiendo al inicio de sesión.", 401);
  }

  let payload = null;
  try {
    payload = await response.json();
  } catch {
    throw new ApiError(`Respuesta no válida del servidor (HTTP ${response.status}).`, response.status);
  }

  if (!response.ok || payload?.ok !== true) {
    const message = payload?.error?.message || `Error HTTP ${response.status}.`;
    throw new ApiError(message, response.status);
  }

  return payload.data;
}

export function fetchOverview(params) {
  return request("overview.php", params);
}

export function fetchLeads(params) {
  return request("leads.php", params);
}

export function buildExportUrl(params) {
  return toUrl("export.php", params).toString();
}
