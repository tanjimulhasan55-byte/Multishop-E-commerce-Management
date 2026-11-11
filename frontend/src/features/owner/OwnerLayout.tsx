import { useEffect, useState } from 'react';
import { Link, NavLink, Outlet, useLocation } from 'react-router-dom';
import { Boxes, ExternalLink, LayoutDashboard, Library, ListChecks, Menu as MenuIcon, Package, Receipt, Store } from 'lucide-react';
import { AccountMenu } from '@/components/layout/AccountMenu';
import { LocaleSwitcher } from '@/components/layout/LocaleSwitcher';
import { Logo } from '@/components/layout/Logo';
import { Button, Drawer } from '@/components/ui';
import { cn } from '@/lib/cn';
import { ShopSwitcher } from './components/ShopSwitcher';

const sections = [
  {
    label: 'Overview',
    items: [{ to: '/owner', label: 'Dashboard', icon: LayoutDashboard, end: true }],
  },
  {
    label: 'Selling',
    items: [
      { to: '/owner/shops', label: 'My shops', icon: Store },
      { to: '/owner/listings', label: 'Listings', icon: ListChecks },
      { to: '/owner/orders', label: 'Orders', icon: Receipt },
    ],
  },
  {
    label: 'Catalog',
    items: [
      { to: '/owner/catalog', label: 'Shared catalog', icon: Library },
      { to: '/owner/products', label: 'My products', icon: Boxes },
    ],
  },
];

function SidebarNav() {
  return (
    <nav aria-label="Owner" className="space-y-6">
      {sections.map((section) => (
        <div key={section.label}>
          <p className="mb-1.5 px-2.5 text-[0.6875rem] font-semibold tracking-wider text-faint uppercase">{section.label}</p>
          <ul className="space-y-0.5">
            {section.items.map(({ to, label, icon: Icon, ...rest }) => (
              <li key={to}>
                <NavLink
                  to={to}
                  end={'end' in rest}
                  className={({ isActive }) =>
                    cn(
                      'group flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium transition-colors duration-150',
                      isActive ? 'bg-surface text-ink shadow-xs ring-1 ring-line' : 'text-ink-soft hover:bg-surface/70 hover:text-ink',
                    )
                  }
                >
                  {({ isActive }) => (
                    <>
                      <Icon className={cn('size-4', isActive ? 'text-brand-600' : 'text-muted group-hover:text-ink-soft')} aria-hidden />
                      {label}
                    </>
                  )}
                </NavLink>
              </li>
            ))}
          </ul>
        </div>
      ))}
    </nav>
  );
}

function SidebarContent() {
  return (
    <div className="flex h-full flex-col gap-6">
      <ShopSwitcher />
      <SidebarNav />
      <div className="mt-auto">
        <Link
          to="/"
          className="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm text-muted transition-colors hover:bg-surface/70 hover:text-ink"
        >
          <ExternalLink className="size-4" aria-hidden /> View storefront
        </Link>
      </div>
    </div>
  );
}

export default function OwnerLayout() {
  const [mobileOpen, setMobileOpen] = useState(false);
  const location = useLocation();
  useEffect(() => setMobileOpen(false), [location.pathname]);

  return (
    <div className="min-h-dvh bg-canvas">
      {/* Desktop sidebar */}
      <aside className="fixed inset-y-0 left-0 z-20 hidden w-64 flex-col border-r border-line bg-subtle/60 lg:flex">
        <div className="flex h-16 items-center px-5">
          <Logo to="/owner" suffix="Seller" />
        </div>
        <div className="flex-1 overflow-y-auto px-3 pb-5">
          <SidebarContent />
        </div>
      </aside>

      <div className="lg:pl-64">
        <header className="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-line bg-surface/80 px-4 backdrop-blur-xl sm:px-6 lg:px-8">
          <Button variant="ghost" iconOnly className="-ml-2 lg:hidden" onClick={() => setMobileOpen(true)} aria-label="Open navigation">
            <MenuIcon className="size-5" />
          </Button>
          <div className="lg:hidden">
            <Logo to="/owner" />
          </div>
          <div className="ml-auto flex items-center gap-2">
            <LocaleSwitcher />
            <AccountMenu />
          </div>
        </header>

        <main className="px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
          <div className="mx-auto max-w-7xl">
            <Outlet />
          </div>
        </main>
      </div>

      <Drawer
        open={mobileOpen}
        onClose={() => setMobileOpen(false)}
        side="left"
        width="sm"
        title={
          <span className="inline-flex items-center gap-2">
            <Package className="size-4 text-brand-600" aria-hidden /> Seller panel
          </span>
        }
      >
        <SidebarContent />
      </Drawer>
    </div>
  );
}
