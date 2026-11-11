import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toFormData, type FormValue } from '@/lib/formData';
import { useLocaleStore } from '@/stores/locale';
import type { CollectionDocument, MetaDocument, Paginated, SingleDocument } from '@/types/api';
import type {
  CatalogAttributeAttributes,
  DashboardStats,
  Listing,
  ListingAttributes,
  OrderAttributes,
  OrderStatus,
  ProductAttributes,
  ProductStatus,
  ShopAttributes,
  Translations,
} from '@/types/models';
import { http } from './client';
import { unwrapMany, unwrapOne } from './jsonapi';
import { cleanParams } from './params';

/* ---------- Payloads ---------- */

export interface CatalogFilters {
  shop?: string;
  search?: string;
  category?: string;
  mine?: boolean;
  not_listed?: boolean;
  page?: number;
  per_page?: number;
}

export interface ShopListingFilters {
  search?: string;
  is_active?: '' | '1' | '0';
  low_stock?: boolean;
  page?: number;
  per_page?: number;
}

export interface OwnerOrderFilters {
  shop?: string;
  status?: string;
  page?: number;
}

export interface ProductPayload {
  name: Translations;
  description: Translations;
  category_id: number | null;
  sku?: string;
  base_price: string;
  status: ProductStatus;
  thumbnail?: File | null;
  remove_thumbnail?: boolean;
}

export interface VariantPricing {
  variant_id: number;
  price: string;
  compare_at_price?: string | null;
  stock: number;
  is_active: boolean;
}

export interface ListingPayload {
  product_id?: number;
  price?: string;
  stock?: number;
  compare_at_price?: string | null;
  title?: Translations;
  description?: Translations;
  is_active?: boolean;
  thumbnail?: File | null;
  remove_thumbnail?: boolean;
  variants?: VariantPricing[];
}

/* ---------- Helpers ---------- */

type Body = Record<string, FormValue>;

/** Strip `undefined` and send JSON, or multipart when a file is attached. */
function encode(payload: Body, method: 'POST' | 'PATCH'): { data: Body | FormData; viaPost: boolean } {
  const hasFile = Object.values(payload).some((v) => v instanceof File);
  if (!hasFile) return { data: payload, viaPost: false };
  const form = toFormData(payload);
  // PHP only parses multipart bodies on POST — spoof the verb.
  if (method === 'PATCH') form.append('_method', 'PATCH');
  return { data: form, viaPost: true };
}

async function send<T>(method: 'POST' | 'PATCH', url: string, payload: Body): Promise<T> {
  const { data, viaPost } = encode(payload, method);
  const res = method === 'POST' || viaPost ? await http.post<T>(url, data) : await http.patch<T>(url, data);
  return res.data;
}

function productBody(p: ProductPayload): Body {
  return {
    name: { en: p.name.en ?? '', bn: p.name.bn || null },
    description: { en: p.description.en || null, bn: p.description.bn || null },
    category_id: p.category_id,
    sku: p.sku?.trim() ? p.sku.trim().toUpperCase() : undefined,
    base_price: p.base_price,
    status: p.status,
    thumbnail: p.thumbnail ?? undefined,
    remove_thumbnail: p.remove_thumbnail || undefined,
  };
}

function listingBody(p: ListingPayload): Body {
  const tr = (t?: Translations) => (t ? { en: t.en || null, bn: t.bn || null } : undefined);
  return {
    product_id: p.product_id,
    price: p.price,
    stock: p.stock,
    compare_at_price: p.compare_at_price || undefined,
    title: tr(p.title),
    description: tr(p.description),
    is_active: p.is_active,
    thumbnail: p.thumbnail ?? undefined,
    remove_thumbnail: p.remove_thumbnail || undefined,
    variants: p.variants?.map((v) => ({
      variant_id: v.variant_id,
      price: v.price,
      compare_at_price: v.compare_at_price || null,
      stock: v.stock,
      is_active: v.is_active,
    })),
  };
}

/* ---------- Requests ---------- */

export const ownerApi = {
  dashboard: async () => (await http.get<MetaDocument<DashboardStats>>('/shop/dashboard')).data.meta,
  shops: async () => unwrapMany((await http.get<CollectionDocument<ShopAttributes>>('/shop/shops')).data).items,
  catalog: async (f: CatalogFilters) =>
    unwrapMany(
      (
        await http.get<CollectionDocument<ProductAttributes>>('/shop/catalog', {
          params: cleanParams({
            shop: f.shop,
            page: f.page,
            per_page: f.per_page ?? 12,
            'filter[search]': f.search,
            'filter[category]': f.category,
            'filter[mine]': f.mine,
            'filter[not_listed]': f.not_listed,
          }),
        })
      ).data,
    ),
  attributes: async () =>
    unwrapMany((await http.get<CollectionDocument<CatalogAttributeAttributes>>('/shop/attributes')).data).items,
  createProduct: async (p: ProductPayload) =>
    unwrapOne(await send<SingleDocument<ProductAttributes>>('POST', '/shop/products', productBody(p))),
  updateProduct: async (id: string, p: ProductPayload) =>
    unwrapOne(await send<SingleDocument<ProductAttributes>>('PATCH', `/shop/products/${id}`, productBody(p))),
  deleteProduct: async (id: string) => {
    await http.delete(`/shop/products/${id}`);
  },
  generateVariants: async (id: string, attribute_value_ids: number[], base_price?: string) =>
    unwrapOne(
      (
        await http.post<SingleDocument<ProductAttributes>>(`/shop/products/${id}/variants/generate`, {
          attribute_value_ids,
          base_price: base_price || undefined,
        })
      ).data,
    ),
  updateVariant: async (variantId: number, body: { sku: string; base_price: string }) => {
    await http.patch(`/shop/variants/${variantId}`, body);
  },
  deleteVariant: async (variantId: number) => {
    await http.delete(`/shop/variants/${variantId}`);
  },
  listings: async (slug: string, f: ShopListingFilters): Promise<Paginated<Listing>> =>
    unwrapMany(
      (
        await http.get<CollectionDocument<ListingAttributes>>(`/shop/shops/${slug}/listings`, {
          params: cleanParams({
            page: f.page,
            per_page: f.per_page ?? 15,
            'filter[search]': f.search,
            'filter[is_active]': f.is_active,
            'filter[low_stock]': f.low_stock,
          }),
        })
      ).data,
    ),
  createListing: async (slug: string, p: ListingPayload) =>
    unwrapOne(await send<SingleDocument<ListingAttributes>>('POST', `/shop/shops/${slug}/listings`, listingBody(p))),
  updateListing: async (id: string, p: ListingPayload) =>
    unwrapOne(await send<SingleDocument<ListingAttributes>>('PATCH', `/shop/listings/${id}`, listingBody(p))),
  deleteListing: async (id: string) => {
    await http.delete(`/shop/listings/${id}`);
  },
  orders: async (f: OwnerOrderFilters) =>
    unwrapMany(
      (
        await http.get<CollectionDocument<OrderAttributes>>('/shop/orders', {
          params: cleanParams({ page: f.page, per_page: 15, 'filter[shop]': f.shop, 'filter[status]': f.status }),
        })
      ).data,
    ),
  updateOrderStatus: async (number: string, status: OrderStatus) =>
    unwrapOne((await http.patch<SingleDocument<OrderAttributes>>(`/shop/orders/${number}/status`, { status })).data),
};

/* ---------- Hooks ---------- */

const useLocale = () => useLocaleStore((s) => s.locale);

export const ownerKeys = {
  all: ['owner'] as const,
  dashboard: ['owner', 'dashboard'] as const,
  shops: ['owner', 'shops'] as const,
  catalog: ['owner', 'catalog'] as const,
  attributes: ['owner', 'attributes'] as const,
  listings: ['owner', 'listings'] as const,
  orders: ['owner', 'orders'] as const,
};

export function useOwnerDashboard() {
  return useQuery({ queryKey: ownerKeys.dashboard, queryFn: ownerApi.dashboard });
}

export function useMyShops() {
  const locale = useLocale();
  return useQuery({ queryKey: [...ownerKeys.shops, locale], queryFn: ownerApi.shops });
}

export function useCatalog(filters: CatalogFilters) {
  const locale = useLocale();
  return useQuery({
    queryKey: [...ownerKeys.catalog, locale, filters],
    queryFn: () => ownerApi.catalog(filters),
    placeholderData: keepPreviousData,
  });
}

export function useAttributes() {
  const locale = useLocale();
  return useQuery({ queryKey: [...ownerKeys.attributes, locale], queryFn: ownerApi.attributes, staleTime: 10 * 60_000 });
}

export function useShopListings(slug: string | null, filters: ShopListingFilters) {
  const locale = useLocale();
  return useQuery({
    queryKey: [...ownerKeys.listings, slug, locale, filters],
    queryFn: () => ownerApi.listings(slug as string, filters),
    enabled: !!slug,
    placeholderData: keepPreviousData,
  });
}

export function useOwnerOrders(filters: OwnerOrderFilters) {
  return useQuery({
    queryKey: [...ownerKeys.orders, filters],
    queryFn: () => ownerApi.orders(filters),
    placeholderData: keepPreviousData,
  });
}

/** Invalidate everything a catalog/listing write can affect. */
function useInvalidateOwner() {
  const qc = useQueryClient();
  return () => {
    void qc.invalidateQueries({ queryKey: ownerKeys.all });
    void qc.invalidateQueries({ queryKey: ['storefront'] });
  };
}

export function useSaveProduct() {
  const invalidate = useInvalidateOwner();
  return useMutation({
    mutationFn: ({ id, payload }: { id?: string; payload: ProductPayload }) =>
      id ? ownerApi.updateProduct(id, payload) : ownerApi.createProduct(payload),
    onSuccess: invalidate,
  });
}

export function useDeleteProduct() {
  const invalidate = useInvalidateOwner();
  return useMutation({ mutationFn: ownerApi.deleteProduct, onSuccess: invalidate });
}

export function useGenerateVariants() {
  const invalidate = useInvalidateOwner();
  return useMutation({
    mutationFn: ({ id, valueIds, basePrice }: { id: string; valueIds: number[]; basePrice?: string }) =>
      ownerApi.generateVariants(id, valueIds, basePrice),
    onSuccess: invalidate,
  });
}

export function useUpdateVariant() {
  const invalidate = useInvalidateOwner();
  return useMutation({
    mutationFn: ({ id, sku, base_price }: { id: number; sku: string; base_price: string }) =>
      ownerApi.updateVariant(id, { sku, base_price }),
    onSuccess: invalidate,
  });
}

export function useDeleteVariant() {
  const invalidate = useInvalidateOwner();
  return useMutation({ mutationFn: ownerApi.deleteVariant, onSuccess: invalidate });
}

export function useCreateListing() {
  const invalidate = useInvalidateOwner();
  return useMutation({
    mutationFn: ({ slug, payload }: { slug: string; payload: ListingPayload }) => ownerApi.createListing(slug, payload),
    onSuccess: invalidate,
  });
}

export function useUpdateListing() {
  const invalidate = useInvalidateOwner();
  return useMutation({
    mutationFn: ({ id, payload }: { id: string; payload: ListingPayload }) => ownerApi.updateListing(id, payload),
    onSuccess: invalidate,
  });
}

/** Active toggle with an optimistic cache update. */
export function useToggleListing() {
  const qc = useQueryClient();
  const invalidate = useInvalidateOwner();
  return useMutation({
    mutationFn: ({ id, payload }: { id: string; payload: ListingPayload }) => ownerApi.updateListing(id, payload),
    onMutate: async ({ id, payload }) => {
      await qc.cancelQueries({ queryKey: ownerKeys.listings });
      const snapshot = qc.getQueriesData<Paginated<Listing>>({ queryKey: ownerKeys.listings });
      qc.setQueriesData<Paginated<Listing>>({ queryKey: ownerKeys.listings }, (old) =>
        old
          ? { ...old, items: old.items.map((l) => (l.id === id ? { ...l, is_active: !!payload.is_active } : l)) }
          : old,
      );
      return { snapshot };
    },
    onError: (_e, _v, ctx) => ctx?.snapshot.forEach(([key, data]) => qc.setQueryData(key, data)),
    onSettled: invalidate,
  });
}

export function useDeleteListing() {
  const invalidate = useInvalidateOwner();
  return useMutation({ mutationFn: ownerApi.deleteListing, onSuccess: invalidate });
}

export function useUpdateOrderStatus() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ number, status }: { number: string; status: OrderStatus }) =>
      ownerApi.updateOrderStatus(number, status),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ownerKeys.orders });
      void qc.invalidateQueries({ queryKey: ownerKeys.dashboard });
    },
  });
}
