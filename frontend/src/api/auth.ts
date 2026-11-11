import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useAuthStore } from '@/stores/auth';
import type { SingleDocument } from '@/types/api';
import type { TokenMeta, User, UserAttributes } from '@/types/models';
import { http } from './client';
import { flatten } from './jsonapi';

type SessionDocument = SingleDocument<UserAttributes, TokenMeta>;

export interface LoginPayload {
  email: string;
  password: string;
}

export interface RegisterPayload {
  name: string;
  email: string;
  phone?: string;
  password: string;
  password_confirmation: string;
}

function storeSession(doc: SessionDocument): User {
  const user = flatten(doc.data);
  if (doc.meta?.access_token) useAuthStore.getState().setSession(doc.meta.access_token, user);
  return user;
}

export const authApi = {
  login: async (payload: LoginPayload) => storeSession((await http.post<SessionDocument>('/auth/login', payload)).data),
  register: async (payload: RegisterPayload) =>
    storeSession((await http.post<SessionDocument>('/auth/register', payload)).data),
  me: async () => flatten((await http.get<SingleDocument<UserAttributes>>('/auth/me')).data.data),
  logout: async () => {
    await http.post('/auth/logout');
  },
};

export const authKeys = { me: ['auth', 'me'] as const };

/** Re-validates the persisted session against `/auth/me` on boot. */
export function useMe() {
  const token = useAuthStore((s) => s.token);
  const setUser = useAuthStore((s) => s.setUser);
  return useQuery({
    queryKey: authKeys.me,
    enabled: !!token,
    staleTime: 5 * 60_000,
    queryFn: async () => {
      const user = await authApi.me();
      setUser(user);
      return user;
    },
  });
}

export function useLogin() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: authApi.login,
    onSuccess: (user) => qc.setQueryData(authKeys.me, user),
  });
}

export function useRegister() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: authApi.register,
    onSuccess: (user) => qc.setQueryData(authKeys.me, user),
  });
}

export function useLogout() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: () => authApi.logout().catch(() => undefined),
    onSettled: () => {
      useAuthStore.getState().clear();
      qc.removeQueries({ queryKey: ['account'] });
      qc.removeQueries({ queryKey: ['owner'] });
      qc.removeQueries({ queryKey: authKeys.me });
    },
  });
}
