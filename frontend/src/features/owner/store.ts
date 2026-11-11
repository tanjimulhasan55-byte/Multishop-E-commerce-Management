import { create } from 'zustand';
import { persist } from 'zustand/middleware';

interface OwnerState {
  /** Slug of the shop the owner is currently managing. */
  shopSlug: string | null;
  setShopSlug: (slug: string | null) => void;
}

export const useOwnerStore = create<OwnerState>()(
  persist((set) => ({ shopSlug: null, setShopSlug: (shopSlug) => set({ shopSlug }) }), { name: 'multishop.owner' }),
);
