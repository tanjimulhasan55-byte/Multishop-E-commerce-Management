import { Outlet } from 'react-router-dom';
import { CartDrawer } from '@/features/cart/components/CartDrawer';
import { StoreFooter } from './StoreFooter';
import { StoreHeader } from './StoreHeader';

export default function StoreLayout() {
  return (
    <div className="flex min-h-dvh flex-col">
      <a
        href="#main"
        className="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-lg focus:bg-ink focus:px-4 focus:py-2 focus:text-sm focus:text-white"
      >
        Skip to content
      </a>
      <StoreHeader />
      <main id="main" className="flex-1">
        <Outlet />
      </main>
      <StoreFooter />
      <CartDrawer />
    </div>
  );
}
