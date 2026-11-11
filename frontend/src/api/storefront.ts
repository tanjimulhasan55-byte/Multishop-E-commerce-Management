import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { useLocaleStore } from '@/stores/locale';
import type { CollectionDocument, MetaDocument, QueryParams, SingleDocument } from '@/types/api';
import type {
  CategoryAttributes,
  ListingAttributes,
  Shop,
  ShopAttributes,
  StorefrontSettings,
} from '@/types/models';
import { http } from './client';
import { unwrapMany, unwrapOne } from './jsonapi';
import { cleanParams } from './params';

export interface ListingFilters {
  page?: number;
  per_page?: number;
  shop?: string;
  category?: string;
  search?: string;
  min_price?: string | number;
  max_price?: string | number;
  in_stock?: boolean;
  options?: number[];
  sort?: string;
}

export interface ShopFilters {
  page?: number;
  per_page?: number;
  search?: string;
  sort?: string;
}

function listingParams(f: ListingFilters): QueryParams {
  return {
    page: f.page,
    per_page: f.per_page,
    sort: f.sort,
    include: 'shop',
    'filter[shop]': f.shop,
    'filter[category]': f.category,
    'filter[search]': f.search,
    'filter[min_price]': f.min_price,
    'filter[max_price]': f.max_price,
    'filter[in_stock]': f.in_stock,
    'filter[options]': f.options?.length ? f.options.join(',') : undefined,
  };
}

export const storefrontApi = {
  settings: async () => (await http.get<MetaDocument<StorefrontSettings>>('/storefront/settings')).data.meta,
  categories: async () =>
    unwrapMany((await http.get<CollectionDocument<CategoryAttributes>>('/storefront/categories')).data).items,
  shops: async (f: ShopFilters) =>
    unwrapMany(
      (
        await http.get<CollectionDocument<ShopAttributes>>('/storefront/shops', {
          params: cleanParams({ page: f.page, per_page: f.per_page, sort: f.sort, 'filter[search]': f.search }),
        })
      ).data,
    ),
  shop: async (slug: string) =>
    unwrapOne((await http.get<SingleDocument<ShopAttributes>>(`/storefront/shops/${slug}`)).data),
  listings: async (f: ListingFilters) =>
    unwrapMany<ListingAttributes, { shop?: Shop }>(
      (await http.get<CollectionDocument<ListingAttributes>>('/storefront/listings', { params: cleanParams(listingParams(f)) }))
        .data,
    ),
  listing: async (id: string) =>
    unwrapOne<ListingAttributes, { shop?: Shop }>(
      (await http.get<SingleDocument<ListingAttributes>>(`/storefront/listings/${id}`, { params: { include: 'shop' } })).data,
    ),
};

const useLocale = () => useLocaleStore((s) => s.locale);

export const storefrontKeys = {
  all: (locale: string) => ['storefront', locale] as const,
};

export function useSettings() {
  return useQuery({
    queryKey: ['storefront', 'settings'],
    queryFn: storefrontApi.settings,
    staleTime: 30 * 60_000,
  });
}

export function useCategories() {
  const locale = useLocale();
  return useQuery({
    queryKey: [...storefrontKeys.all(locale), 'categories'],
    queryFn: storefrontApi.categories,
    staleTime: 10 * 60_000,
  });
}

export function useShops(filters: ShopFilters) {
  const locale = useLocale();
  return useQuery({
    queryKey: [...storefrontKeys.all(locale), 'shops', filters],
    queryFn: () => storefrontApi.shops(filters),
    placeholderData: keepPreviousData,
  });
}

export function useShop(slug: string | undefined) {
  const locale = useLocale();
  return useQuery({
    queryKey: [...storefrontKeys.all(locale), 'shop', slug],
    queryFn: () => storefrontApi.shop(slug as string),
    enabled: !!slug,
  });
}

export function useListings(filters: ListingFilters, options: { enabled?: boolean } = {}) {
  const locale = useLocale();
  return useQuery({
    queryKey: [...storefrontKeys.all(locale), 'listings', filters],
    queryFn: () => storefrontApi.listings(filters),
    placeholderData: keepPreviousData,
    enabled: options.enabled ?? true,
  });
}

export function useListing(id: string | undefined) {
  const locale = useLocale();
  return useQuery({
    queryKey: [...storefrontKeys.all(locale), 'listing', id],
    queryFn: () => storefrontApi.listing(id as string),
    enabled: !!id,
  });
}
