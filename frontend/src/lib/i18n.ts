import { useLocaleStore } from '@/stores/locale';
import type { Locale } from '@/types/models';

/**
 * A deliberately tiny UI dictionary for navigation chrome.
 * Catalogue content (product titles, categories, shops) arrives translated
 * from the API via the `Accept-Language` header.
 */
const dictionary = {
  home: { en: 'Home', bn: 'হোম' },
  products: { en: 'Products', bn: 'পণ্য' },
  shops: { en: 'Shops', bn: 'দোকান' },
  cart: { en: 'Cart', bn: 'কার্ট' },
  account: { en: 'Account', bn: 'অ্যাকাউন্ট' },
  orders: { en: 'My orders', bn: 'আমার অর্ডার' },
  signIn: { en: 'Sign in', bn: 'সাইন ইন' },
  signUp: { en: 'Create account', bn: 'অ্যাকাউন্ট খুলুন' },
  signOut: { en: 'Sign out', bn: 'সাইন আউট' },
  ownerPanel: { en: 'Owner panel', bn: 'মালিক প্যানেল' },
  adminPanel: { en: 'Admin panel', bn: 'অ্যাডমিন প্যানেল' },
  search: { en: 'Search products…', bn: 'পণ্য খুঁজুন…' },
  categories: { en: 'Categories', bn: 'ক্যাটাগরি' },
} satisfies Record<string, Record<Locale, string>>;

export type TranslationKey = keyof typeof dictionary;

export function useT(): (key: TranslationKey) => string {
  const locale = useLocaleStore((s) => s.locale);
  return (key) => dictionary[key][locale];
}
