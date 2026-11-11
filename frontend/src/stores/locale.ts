import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import type { Locale } from '@/types/models';

interface LocaleState {
  locale: Locale;
  setLocale: (locale: Locale) => void;
}

export const useLocaleStore = create<LocaleState>()(
  persist(
    (set) => ({
      locale: 'en',
      setLocale: (locale) => {
        document.documentElement.lang = locale;
        set({ locale });
      },
    }),
    {
      name: 'multishop.locale',
      onRehydrateStorage: () => (state) => {
        if (state) document.documentElement.lang = state.locale;
      },
    },
  ),
);
