import { KEYS, storage } from './storage';

/** Hospital test server. Users can change it from the login screen. */
export const DEFAULT_SERVER_URL = 'http://172.16.10.105:9091';

const REQUEST_TIMEOUT_MS = 15000;

export class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
    /** true when the server could not be reached at all (not on hospital Wi-Fi) */
    public offline = false,
    public errors: Record<string, string[]> = {},
    public code?: string,
  ) {
    super(message);
  }
}

let token: string | null = null;
let serverUrl = DEFAULT_SERVER_URL;
let onUnauthorized: (() => void) | null = null;

export async function loadSession() {
  token = await storage.get(KEYS.token);
  serverUrl = (await storage.get(KEYS.serverUrl)) || DEFAULT_SERVER_URL;
  return { token, serverUrl };
}

export async function setToken(value: string | null) {
  token = value;
  if (value) {
    await storage.set(KEYS.token, value);
  } else {
    await storage.remove(KEYS.token);
  }
}

export async function setServerUrl(value: string) {
  serverUrl = value.trim().replace(/\/+$/, '');
  await storage.set(KEYS.serverUrl, serverUrl);
}

export function getServerUrl() {
  return serverUrl;
}

export function setUnauthorizedHandler(handler: () => void) {
  onUnauthorized = handler;
}

type Options = {
  method?: 'GET' | 'POST' | 'DELETE';
  body?: unknown;
  form?: FormData;
};

export async function api<T>(path: string, { method = 'GET', body, form }: Options = {}): Promise<T> {
  const headers: Record<string, string> = { Accept: 'application/json' };
  if (token) headers.Authorization = `Bearer ${token}`;
  if (body !== undefined) headers['Content-Type'] = 'application/json';

  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), REQUEST_TIMEOUT_MS);

  let response: Response;
  try {
    response = await fetch(`${serverUrl}/api/v1${path}`, {
      method,
      headers,
      body: form ?? (body !== undefined ? JSON.stringify(body) : undefined),
      signal: controller.signal,
    });
  } catch {
    throw new ApiError('تعذر الاتصال بالخادم. تأكد من اتصالك بشبكة Wi-Fi المستشفى.', 0, true);
  } finally {
    clearTimeout(timer);
  }

  const data = await response.json().catch(() => ({}));

  if (!response.ok) {
    if (response.status === 401 && token) {
      onUnauthorized?.();
    }
    const firstError = data?.errors ? (Object.values(data.errors)[0] as string[] | undefined)?.[0] : undefined;
    const message =
      firstError ||
      data?.message ||
      (response.status === 403 ? 'لا تملك صلاحية تنفيذ هذا الإجراء.' : 'حدث خطأ غير متوقع، حاول مرة أخرى.');
    throw new ApiError(message, response.status, false, data?.errors ?? {}, data?.code);
  }

  return data as T;
}
