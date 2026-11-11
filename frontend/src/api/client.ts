import axios, { AxiosHeaders, type AxiosError, type InternalAxiosRequestConfig } from 'axios';
import { useAuthStore } from '@/stores/auth';
import { useLocaleStore } from '@/stores/locale';
import type { SingleDocument } from '@/types/api';
import type { TokenMeta, UserAttributes } from '@/types/models';
import { flatten } from './jsonapi';
import { toApiError } from './errors';

export const API_URL = (import.meta.env.VITE_API_URL ?? 'http://127.0.0.1:8000/api/v1').replace(/\/$/, '');

export const http = axios.create({
  baseURL: API_URL,
  headers: { Accept: 'application/json' },
  timeout: 30_000,
});

type RetriableConfig = InternalAxiosRequestConfig & { _retried?: boolean; _skipRefresh?: boolean };

/* ---------- Request: auth + locale headers ---------- */

http.interceptors.request.use((config) => {
  const headers = AxiosHeaders.from(config.headers);
  const { token } = useAuthStore.getState();
  if (token && !headers.has('Authorization')) headers.set('Authorization', `Bearer ${token}`);
  headers.set('Accept-Language', useLocaleStore.getState().locale);
  config.headers = headers;
  return config;
});

/* ---------- Response: one refresh attempt on 401, queued ---------- */

let refreshing: Promise<string> | null = null;

async function refreshToken(): Promise<string> {
  const current = useAuthStore.getState().token;
  if (!current) throw new Error('No session');
  // A bare axios call so this request never re-enters the interceptor.
  const { data } = await axios.post<SingleDocument<UserAttributes, TokenMeta>>(`${API_URL}/auth/refresh`, null, {
    headers: { Accept: 'application/json', Authorization: `Bearer ${current}` },
  });
  const token = data.meta?.access_token;
  if (!token) throw new Error('Refresh failed');
  useAuthStore.getState().setSession(token, flatten(data.data));
  return token;
}

const AUTH_ENDPOINTS = ['/auth/login', '/auth/register', '/auth/refresh'];

http.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    const config = error.config as RetriableConfig | undefined;
    const isAuthEndpoint = AUTH_ENDPOINTS.some((p) => config?.url?.endsWith(p));
    const hasSession = !!useAuthStore.getState().token;

    if (error.response?.status === 401 && config && !config._retried && !config._skipRefresh && !isAuthEndpoint && hasSession) {
      config._retried = true;
      try {
        // All concurrent 401s share a single in-flight refresh.
        refreshing ??= refreshToken().finally(() => {
          refreshing = null;
        });
        const token = await refreshing;
        const headers = AxiosHeaders.from(config.headers);
        headers.set('Authorization', `Bearer ${token}`);
        config.headers = headers;
        return http(config);
      } catch {
        useAuthStore.getState().clear();
        window.dispatchEvent(new CustomEvent('auth:expired'));
      }
    }
    return Promise.reject(toApiError(error));
  },
);
