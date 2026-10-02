// Client HTTP de l'API BookSpace (backend Laravel).
// Le jeton Sanctum est conservé en localStorage et envoyé en Bearer.

const BASE_URL = import.meta.env.VITE_API_URL ?? "http://localhost:8000/api";
const TOKEN_KEY = "bookspace_token";

/** Émis quand l'API renvoie 401 : l'AuthContext efface l'utilisateur courant. */
export const UNAUTHORIZED_EVENT = "bookspace:unauthorized";

export function getToken(): string | null {
  try {
    return localStorage.getItem(TOKEN_KEY);
  } catch {
    return null;
  }
}

export function setToken(token: string | null) {
  try {
    if (token) localStorage.setItem(TOKEN_KEY, token);
    else localStorage.removeItem(TOKEN_KEY);
  } catch {
    /* stockage indisponible — session non persistée */
  }
}

export class ApiError extends Error {
  status: number;
  errors: Record<string, string[]>;

  constructor(status: number, message: string, errors: Record<string, string[]> = {}) {
    super(message);
    this.status = status;
    this.errors = errors;
  }

  /** Premier message d'erreur de validation, sinon le message général. */
  get premierMessage(): string {
    const cle = Object.keys(this.errors)[0];
    return cle ? this.errors[cle][0] : this.message;
  }
}

type Options = {
  method?: string;
  body?: unknown;
  params?: Record<string, string | number | undefined | null>;
};

export async function apiFetch<T = unknown>(path: string, options: Options = {}): Promise<T> {
  const { method = "GET", body, params } = options;

  const url = new URL(`${BASE_URL}${path}`);
  if (params) {
    for (const [k, v] of Object.entries(params)) {
      if (v !== undefined && v !== null && v !== "") url.searchParams.set(k, String(v));
    }
  }

  const headers: Record<string, string> = { Accept: "application/json" };
  const token = getToken();
  if (token) headers.Authorization = `Bearer ${token}`;
  if (body !== undefined) headers["Content-Type"] = "application/json";

  let res: Response;
  try {
    res = await fetch(url.toString(), {
      method,
      headers,
      body: body !== undefined ? JSON.stringify(body) : undefined,
    });
  } catch {
    // Connexion refusée / serveur injoignable / hors ligne.
    throw new ApiError(0, "Impossible de joindre le serveur. Vérifiez votre connexion et que l'API est démarrée.");
  }

  if (res.status === 204) return undefined as T;

  const data = await res.json().catch(() => ({}));

  if (!res.ok) {
    if (res.status === 401) signalerDeconnexion();
    throw new ApiError(res.status, data.message ?? "Une erreur est survenue.", data.errors ?? {});
  }

  return data as T;
}

/** Jeton expiré / invalide : on le purge et on prévient l'app (redirection login). */
function signalerDeconnexion() {
  setToken(null);
  if (typeof window !== "undefined") {
    window.dispatchEvent(new Event(UNAUTHORIZED_EVENT));
  }
}

/** Envoi de fichiers (multipart) — ne PAS fixer Content-Type (le navigateur gère la limite). */
export async function apiUpload<T = unknown>(path: string, form: FormData): Promise<T> {
  const headers: Record<string, string> = { Accept: "application/json" };
  const token = getToken();
  if (token) headers.Authorization = `Bearer ${token}`;

  let res: Response;
  try {
    res = await fetch(`${BASE_URL}${path}`, { method: "POST", headers, body: form });
  } catch {
    throw new ApiError(0, "Impossible de joindre le serveur.");
  }

  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    if (res.status === 401) signalerDeconnexion();
    throw new ApiError(res.status, data.message ?? "Échec de l'envoi.", data.errors ?? {});
  }
  return data as T;
}

export const api = {
  get: <T>(path: string, params?: Options["params"]) => apiFetch<T>(path, { params }),
  post: <T>(path: string, body?: unknown) => apiFetch<T>(path, { method: "POST", body }),
  put: <T>(path: string, body?: unknown) => apiFetch<T>(path, { method: "PUT", body }),
  patch: <T>(path: string, body?: unknown) => apiFetch<T>(path, { method: "PATCH", body }),
  del: <T>(path: string) => apiFetch<T>(path, { method: "DELETE" }),
  upload: apiUpload,
};
