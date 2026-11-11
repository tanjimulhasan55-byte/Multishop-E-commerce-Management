import { NavLink, Outlet } from 'react-router-dom';
import { KeyRound, Package, UserRound } from 'lucide-react';
import { Avatar } from '@/components/layout/AccountMenu';
import { cn } from '@/lib/cn';
import { useAuthStore } from '@/stores/auth';

const links = [
  { to: '/account', label: 'Profile', icon: UserRound, end: true },
  { to: '/account/security', label: 'Password', icon: KeyRound, end: true },
  { to: '/account/orders', label: 'Orders', icon: Package, end: false },
];

export default function AccountLayout() {
  const user = useAuthStore((s) => s.user);

  return (
    <div className="container-page py-8 sm:py-12">
      <div className="grid gap-8 lg:grid-cols-[15rem_1fr] lg:gap-12">
        <aside>
          {user && (
            <div className="mb-6 flex items-center gap-3">
              <Avatar name={user.name} className="size-11 text-sm" />
              <div className="min-w-0">
                <p className="truncate text-sm font-semibold">{user.name}</p>
                <p className="truncate text-[0.8125rem] text-muted">{user.email}</p>
              </div>
            </div>
          )}
          <nav aria-label="Account" className="scrollbar-none -mx-4 flex gap-1 overflow-x-auto px-4 lg:mx-0 lg:flex-col lg:px-0">
            {links.map(({ to, label, icon: Icon, end }) => (
              <NavLink
                key={to}
                to={to}
                end={end}
                className={({ isActive }) =>
                  cn(
                    'flex shrink-0 items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-150',
                    isActive ? 'bg-surface text-ink shadow-xs ring-1 ring-line' : 'text-muted hover:bg-subtle hover:text-ink',
                  )
                }
              >
                <Icon className="size-4" aria-hidden />
                {label}
              </NavLink>
            ))}
          </nav>
        </aside>
        <div className="min-w-0">
          <Outlet />
        </div>
      </div>
    </div>
  );
}
