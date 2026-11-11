import { useEffect, useState } from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import { Menu as MenuIcon, ShoppingBag } from 'lucide-react';
import { Button, ButtonLink, Drawer } from '@/components/ui';
import { useCartCount, useCartDrawer } from '@/features/cart/store';
import { cn } from '@/lib/cn';
import { useT, type TranslationKey } from '@/lib/i18n';
import { isShopOwner, useAuthStore } from '@/stores/auth';
import { AccountMenu } from './AccountMenu';
import { LocaleSwitcher } from './LocaleSwitcher';
import { Logo } from './Logo';
import { SearchBox } from './SearchBox';
import { loginUrl } from '@/features/auth/guards';

const nav: { to: string; key: TranslationKey; end?: boolean }[] = [
  { to: '/', key: 'home', end: true },
  { to: '/products', key: 'products' },
  { to: '/shops', key: 'shops' },
];

export function StoreHeader() {
  const t = useT();
  const user = useAuthStore((s) => s.user);
  const count = useCartCount();
  const openCart = useCartDrawer((s) => s.setOpen);
  const [mobileOpen, setMobileOpen] = useState(false);
  const [scrolled, setScrolled] = useState(false);
  const location = useLocation();

  useEffect(() => setMobileOpen(false), [location.pathname]);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 4);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  return (
    <header
      className={cn(
        'sticky top-0 z-30 border-b bg-surface/80 backdrop-blur-xl backdrop-saturate-150 transition-[border-color,box-shadow] duration-200',
        scrolled ? 'border-line shadow-xs' : 'border-transparent',
      )}
    >
      <div className="container-page flex h-16 items-center gap-3 lg:gap-8">
        <Button variant="ghost" iconOnly className="-ml-2 md:hidden" onClick={() => setMobileOpen(true)} aria-label="Open menu">
          <MenuIcon className="size-5" />
        </Button>

        <Logo />

        <nav aria-label="Primary" className="hidden items-center gap-1 md:flex">
          {nav.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              end={item.end}
              className={({ isActive }) =>
                cn(
                  'rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-150',
                  isActive ? 'text-ink' : 'text-muted hover:text-ink',
                )
              }
            >
              {t(item.key)}
            </NavLink>
          ))}
        </nav>

        <SearchBox className="hidden flex-1 md:block lg:max-w-md" />

        <div className="ml-auto flex items-center gap-1.5 sm:gap-2">
          <LocaleSwitcher className="hidden sm:inline-flex" />

          <Button
            variant="ghost"
            iconOnly
            onClick={() => openCart(true)}
            aria-label={`${t('cart')}, ${count} item${count === 1 ? '' : 's'}`}
            className="relative"
          >
            <ShoppingBag className="size-5" />
            {count > 0 && (
              <span className="absolute -top-0.5 -right-0.5 flex h-[1.125rem] min-w-[1.125rem] animate-scale-in items-center justify-center rounded-full bg-brand-600 px-1 text-[0.6875rem] font-semibold text-white ring-2 ring-surface tabular-nums">
                {count > 99 ? '99+' : count}
              </span>
            )}
          </Button>

          {user ? (
            <AccountMenu />
          ) : (
            <>
              <ButtonLink
                to={loginUrl(location.pathname + location.search)}
                variant="ghost"
                size="sm"
                className="hidden sm:inline-flex"
              >
                {t('signIn')}
              </ButtonLink>
              <ButtonLink to="/register" size="sm">
                {t('signUp')}
              </ButtonLink>
            </>
          )}
        </div>
      </div>

      <Drawer open={mobileOpen} onClose={() => setMobileOpen(false)} side="left" width="sm" title={<Logo />}>
        <div className="space-y-6">
          <SearchBox onSubmitted={() => setMobileOpen(false)} />
          <nav aria-label="Mobile" className="flex flex-col gap-1">
            {nav.map((item) => (
              <NavLink
                key={item.to}
                to={item.to}
                end={item.end}
                className={({ isActive }) =>
                  cn('rounded-lg px-3 py-2.5 text-[0.9375rem] font-medium', isActive ? 'bg-brand-50 text-brand-700' : 'text-ink-soft hover:bg-subtle')
                }
              >
                {t(item.key)}
              </NavLink>
            ))}
            {user && (
              <NavLink to="/account/orders" className="rounded-lg px-3 py-2.5 text-[0.9375rem] font-medium text-ink-soft hover:bg-subtle">
                {t('orders')}
              </NavLink>
            )}
            {isShopOwner(user) && (
              <NavLink to="/owner" className="rounded-lg px-3 py-2.5 text-[0.9375rem] font-medium text-ink-soft hover:bg-subtle">
                {t('ownerPanel')}
              </NavLink>
            )}
            {!user && (
              <NavLink to="/login" className="rounded-lg px-3 py-2.5 text-[0.9375rem] font-medium text-ink-soft hover:bg-subtle">
                {t('signIn')}
              </NavLink>
            )}
          </nav>
          <div className="flex items-center justify-between border-t border-line pt-5">
            <span className="text-sm text-muted">Language</span>
            <LocaleSwitcher />
          </div>
        </div>
      </Drawer>
    </header>
  );
}
