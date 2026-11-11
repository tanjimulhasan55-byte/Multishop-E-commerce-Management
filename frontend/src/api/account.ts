import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useAuthStore } from '@/stores/auth';
import { useLocaleStore } from '@/stores/locale';
import type { CollectionDocument, MetaDocument, SingleDocument } from '@/types/api';
import type { OrderAttributes, PlaceOrderPayload, UserAttributes } from '@/types/models';
import { authKeys } from './auth';
import { http } from './client';
import { flatten, unwrapMany, unwrapOne } from './jsonapi';

export interface ProfilePayload {
  name: string;
  email: string;
  phone?: string;
}

export interface PasswordPayload {
  current_password: string;
  password: string;
  password_confirmation: string;
}

export const accountApi = {
  updateProfile: async (payload: ProfilePayload) =>
    flatten((await http.patch<SingleDocument<UserAttributes>>('/account/profile', payload)).data.data),
  changePassword: async (payload: PasswordPayload) => {
    await http.put('/account/password', payload);
  },
  orders: async (page: number) =>
    unwrapMany(
      (await http.get<CollectionDocument<OrderAttributes>>('/account/orders', { params: { page, per_page: 10 } })).data,
    ),
  order: async (number: string) =>
    unwrapOne((await http.get<SingleDocument<OrderAttributes>>(`/account/orders/${number}`)).data),
  placeOrder: async (payload: PlaceOrderPayload) =>
    unwrapOne((await http.post<SingleDocument<OrderAttributes>>('/account/orders', payload)).data),
  payWithSslCommerz: async (number: string) =>
    (await http.post<MetaDocument<{ redirect_url: string }>>(`/payments/orders/${number}/sslcommerz`)).data.meta
      .redirect_url,
};

export const accountKeys = {
  orders: ['account', 'orders'] as const,
  order: (number: string) => ['account', 'order', number] as const,
};

export function useUpdateProfile() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: accountApi.updateProfile,
    onSuccess: (user) => {
      useAuthStore.getState().setUser(user);
      qc.setQueryData(authKeys.me, user);
    },
  });
}

export function useChangePassword() {
  return useMutation({ mutationFn: accountApi.changePassword });
}

export function useMyOrders(page: number) {
  const locale = useLocaleStore((s) => s.locale);
  return useQuery({
    queryKey: [...accountKeys.orders, locale, page],
    queryFn: () => accountApi.orders(page),
    placeholderData: keepPreviousData,
  });
}

export function useMyOrder(number: string | undefined) {
  return useQuery({
    queryKey: accountKeys.order(number ?? ''),
    queryFn: () => accountApi.order(number as string),
    enabled: !!number,
  });
}

export function usePlaceOrder() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: accountApi.placeOrder,
    onSuccess: (order) => {
      qc.setQueryData(accountKeys.order(order.number), order);
      void qc.invalidateQueries({ queryKey: accountKeys.orders });
      // Stock changed.
      void qc.invalidateQueries({ queryKey: ['storefront'] });
    },
  });
}

export function usePayOnline() {
  return useMutation({
    mutationFn: accountApi.payWithSslCommerz,
    onSuccess: (url) => {
      window.location.assign(url);
    },
  });
}
