import type { ReactNode } from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { ShieldAlert } from 'lucide-react';
import { ButtonLink, EmptyState } from '@/components/ui';
import { isShopOwner, useAuthStore } from '@/stores/auth';

/** Build a login URL that returns to `path` afterwards. */
export function loginUrl(path: string): string {
  return path && path !== '/' && !path.startsWith('/login') ? `/login?redirect=${encodeURIComponent(path)}` : '/login';
}

/** Only allow internal redirects (prevents open-redirects via ?redirect=). */
export function safeRedirect(value: string | null, fallback = '/'): string {
  return value && value.startsWith('/') && !value.startsWith('//') ? value : fallback;
}

export function RequireAuth({ children }: { children: ReactNode }) {
  const token = useAuthStore((s) => s.token);
  const location = useLocation();
  if (!token) return <Navigate to={loginUrl(location.pathname + location.search)} replace />;
  return <>{children}</>;
}

export function RequireShopOwner({ children }: { children: ReactNode }) {
  const user = useAuthStore((s) => s.user);
  const location = useLocation();
  if (!user) return <Navigate to={loginUrl(location.pathname + location.search)} replace />;
  if (!isShopOwner(user)) {
    return (
      <div className="flex min-h-dvh items-center justify-center bg-canvas">
        <EmptyState
          icon={ShieldAlert}
          title="Shop owners only"
          description="This area is for shop owners. Sign in with a shop owner account to manage listings and orders."
          action={<ButtonLink to="/">Back to store</ButtonLink>}
        />
      </div>
    );
  }
  return <>{children}</>;
}

/** Redirect already signed-in users away from login/register. */
export function GuestOnly({ children }: { children: ReactNode }) {
  const user = useAuthStore((s) => s.user);
  const location = useLocation();
  if (user) {
    const redirect = safeRedirect(new URLSearchParams(location.search).get('redirect'), isShopOwner(user) ? '/owner' : '/');
    return <Navigate to={redirect} replace />;
  }
  return <>{children}</>;
}
