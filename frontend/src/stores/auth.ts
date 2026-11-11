import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import type { User } from '@/types/models';

interface AuthState {
  token: string | null;
  user: User | null;
  setSession: (token: string, user: User) => void;
  setUser: (user: User) => void;
  clear: () => void;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      token: null,
      user: null,
      setSession: (token, user) => set({ token, user }),
      setUser: (user) => set({ user }),
      clear: () => set({ token: null, user: null }),
    }),
    { name: 'multishop.auth', version: 1 },
  ),
);

export const hasRole = (user: User | null, role: string): boolean => !!user?.roles.includes(role);

export const isShopOwner = (user: User | null): boolean => hasRole(user, 'shop_owner');
export const isAdmin = (user: User | null): boolean => hasRole(user, 'admin');
