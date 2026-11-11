import { useEffect } from 'react';
import { useMyShops } from '@/api/owner';
import { useOwnerStore } from './store';

/** The shop being managed; falls back to the first owned shop. */
export function useCurrentShop() {
  const shops = useMyShops();
  const { shopSlug, setShopSlug } = useOwnerStore();
  const list = shops.data ?? [];
  const shop = list.find((s) => s.slug === shopSlug) ?? list[0] ?? null;

  useEffect(() => {
    if (shop && shop.slug !== shopSlug) setShopSlug(shop.slug);
  }, [shop, shopSlug, setShopSlug]);

  return { shop, shops: list, isLoading: shops.isLoading, setShopSlug };
}
