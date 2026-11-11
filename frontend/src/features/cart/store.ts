import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import { toNumber } from '@/lib/format';
import type { ImageSet, Money } from '@/types/models';

export const MAX_QTY = 100;

export interface CartItem {
  /** Shop-variant id — sent as `items[].variant_id` at checkout. */
  variantId: number;
  listingId: string;
  shopId: number;
  shopName: string;
  shopSlug: string | null;
  title: string;
  variantLabel: string | null;
  sku: string;
  price: Money;
  compareAtPrice: Money | null;
  image: ImageSet | null;
  quantity: number;
  /** Stock known when the item was added — caps quantity client-side. */
  stock: number;
}

export interface CartGroup {
  shopId: number;
  shopName: string;
  shopSlug: string | null;
  items: CartItem[];
  subtotal: number;
  count: number;
}

interface CartState {
  items: CartItem[];
  add: (item: Omit<CartItem, 'quantity'>, quantity: number) => void;
  setQuantity: (variantId: number, quantity: number) => void;
  remove: (variantId: number) => void;
  clearShop: (shopId: number) => void;
  clear: () => void;
}

const clampQty = (qty: number, stock: number) => Math.max(1, Math.min(qty, stock > 0 ? stock : 1, MAX_QTY));

export const useCartStore = create<CartState>()(
  persist(
    (set) => ({
      items: [],
      add: (item, quantity) =>
        set((s) => {
          const existing = s.items.find((i) => i.variantId === item.variantId);
          if (existing) {
            return {
              items: s.items.map((i) =>
                i.variantId === item.variantId
                  ? { ...i, ...item, quantity: clampQty(i.quantity + quantity, item.stock) }
                  : i,
              ),
            };
          }
          return { items: [...s.items, { ...item, quantity: clampQty(quantity, item.stock) }] };
        }),
      setQuantity: (variantId, quantity) =>
        set((s) => ({
          items: s.items.map((i) => (i.variantId === variantId ? { ...i, quantity: clampQty(quantity, i.stock) } : i)),
        })),
      remove: (variantId) => set((s) => ({ items: s.items.filter((i) => i.variantId !== variantId) })),
      clearShop: (shopId) => set((s) => ({ items: s.items.filter((i) => i.shopId !== shopId) })),
      clear: () => set({ items: [] }),
    }),
    { name: 'multishop.cart', version: 1 },
  ),
);

export function groupByShop(items: CartItem[]): CartGroup[] {
  const groups = new Map<number, CartGroup>();
  for (const item of items) {
    const g = groups.get(item.shopId) ?? {
      shopId: item.shopId,
      shopName: item.shopName,
      shopSlug: item.shopSlug,
      items: [],
      subtotal: 0,
      count: 0,
    };
    g.items.push(item);
    g.subtotal += toNumber(item.price) * item.quantity;
    g.count += item.quantity;
    groups.set(item.shopId, g);
  }
  return [...groups.values()];
}

/** Cart drawer visibility (not persisted). */
export const useCartDrawer = create<{ open: boolean; setOpen: (open: boolean) => void }>()((set) => ({
  open: false,
  setOpen: (open) => set({ open }),
}));

export const useCartCount =() => useCartStore((s) => s.items.reduce((n, i) => n + i.quantity, 0));
