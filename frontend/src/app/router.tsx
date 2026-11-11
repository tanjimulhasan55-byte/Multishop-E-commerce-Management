import { lazy, Suspense, type ReactNode } from 'react';
import { createBrowserRouter, Outlet } from 'react-router-dom';
import { PageFallback } from '@/components/layout/PageFallback';
import StoreLayout from '@/components/layout/StoreLayout';
import { GuestOnly, RequireAuth, RequireShopOwner } from '@/features/auth/guards';
import HomePage from '@/features/storefront/pages/HomePage';
import { SessionWatcher } from './SessionWatcher';

// Storefront
const ProductsPage = lazy(() => import('@/features/storefront/pages/ProductsPage'));
const ProductPage = lazy(() => import('@/features/storefront/pages/ProductPage'));
const ShopsPage = lazy(() => import('@/features/storefront/pages/ShopsPage'));
const ShopPage = lazy(() => import('@/features/storefront/pages/ShopPage'));
const NotFoundPage = lazy(() => import('@/features/storefront/pages/NotFoundPage'));
const CartPage = lazy(() => import('@/features/cart/CartPage'));
const CheckoutPage = lazy(() => import('@/features/checkout/CheckoutPage'));
// Auth
const LoginPage = lazy(() => import('@/features/auth/LoginPage'));
const RegisterPage = lazy(() => import('@/features/auth/RegisterPage'));
// Account
const AccountLayout = lazy(() => import('@/features/account/AccountLayout'));
const ProfilePage = lazy(() => import('@/features/account/ProfilePage'));
const PasswordPage = lazy(() => import('@/features/account/PasswordPage'));
const OrdersPage = lazy(() => import('@/features/account/OrdersPage'));
const OrderDetailPage = lazy(() => import('@/features/account/OrderDetailPage'));
// Owner panel
const OwnerLayout = lazy(() => import('@/features/owner/OwnerLayout'));
const DashboardPage = lazy(() => import('@/features/owner/pages/DashboardPage'));
const OwnerShopsPage = lazy(() => import('@/features/owner/pages/ShopsPage'));
const ListingsPage = lazy(() => import('@/features/owner/pages/ListingsPage'));
const CatalogPage = lazy(() => import('@/features/owner/pages/CatalogPage'));
const OwnerProductsPage = lazy(() => import('@/features/owner/pages/ProductsPage'));
const OwnerOrdersPage = lazy(() => import('@/features/owner/pages/OrdersPage'));

const page = (node: ReactNode) => <Suspense fallback={<PageFallback />}>{node}</Suspense>;

function Root() {
  return (
    <>
      <SessionWatcher />
      <Outlet />
    </>
  );
}

export const router = createBrowserRouter([
  {
    element: <Root />,
    children: [
      {
        element: <StoreLayout />,
        children: [
          { index: true, element: <HomePage /> },
          { path: 'products', element: page(<ProductsPage />) },
          { path: 'products/:id', element: page(<ProductPage />) },
          { path: 'shops', element: page(<ShopsPage />) },
          { path: 'shops/:slug', element: page(<ShopPage />) },
          { path: 'cart', element: page(<CartPage />) },
          { path: 'checkout/:shopId', element: <RequireAuth>{page(<CheckoutPage />)}</RequireAuth> },
          {
            path: 'account',
            element: <RequireAuth>{page(<AccountLayout />)}</RequireAuth>,
            children: [
              { index: true, element: page(<ProfilePage />) },
              { path: 'security', element: page(<PasswordPage />) },
              { path: 'orders', element: page(<OrdersPage />) },
              { path: 'orders/:number', element: page(<OrderDetailPage />) },
            ],
          },
          { path: '*', element: page(<NotFoundPage />) },
        ],
      },
      { path: 'login', element: <GuestOnly>{page(<LoginPage />)}</GuestOnly> },
      { path: 'register', element: <GuestOnly>{page(<RegisterPage />)}</GuestOnly> },
      {
        path: 'owner',
        element: <RequireShopOwner>{page(<OwnerLayout />)}</RequireShopOwner>,
        children: [
          { index: true, element: page(<DashboardPage />) },
          { path: 'shops', element: page(<OwnerShopsPage />) },
          { path: 'listings', element: page(<ListingsPage />) },
          { path: 'catalog', element: page(<CatalogPage />) },
          { path: 'products', element: page(<OwnerProductsPage />) },
          { path: 'orders', element: page(<OwnerOrdersPage />) },
        ],
      },
    ],
  },
]);
