import { useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';
import { ExternalLink, LayoutDashboard, LogOut, Package, UserRound } from 'lucide-react';
import { useLogout } from '@/api/auth';
import { buttonClasses } from '@/components/ui';
import { Menu, MenuButton, MenuLink, MenuSeparator } from '@/components/ui/Menu';
import { useT } from '@/lib/i18n';
import { isAdmin, isShopOwner, useAuthStore } from '@/stores/auth';

export const ADMIN_URL = import.meta.env.VITE_ADMIN_URL ?? 'http://127.0.0.1:8000/admin';

export function initials(name: string): string {
  return name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((p) => p[0]?.toUpperCase())
    .join('');
}

export function Avatar({ name, className = 'size-8 text-xs' }: { name: string; className?: string }) {
  return (
    <span
      aria-hidden
      className={`flex shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-100 to-brand-200 font-semibold text-brand-800 ${className}`}
    >
      {initials(name)}
    </span>
  );
}

export function AccountMenu() {
  const user = useAuthStore((s) => s.user);
  const t = useT();
  const logout = useLogout();
  const navigate = useNavigate();

  if (!user) return null;

  const signOut = () =>
    logout.mutate(undefined, {
      onSettled: () => {
        toast.success('Signed out');
        navigate('/');
      },
    });

  return (
    <Menu
      trigger={(props) => (
        <button
          type="button"
          {...props}
          aria-label={`Account menu for ${user.name}`}
          className={buttonClasses({ variant: 'ghost', size: 'md' }) + ' gap-2 px-1.5 sm:px-2'}
        >
          <Avatar name={user.name} />
          <span className="hidden max-w-28 truncate text-sm font-medium lg:block">{user.name.split(' ')[0]}</span>
        </button>
      )}
    >
      <div className="px-2.5 pt-1.5 pb-2.5">
        <p className="truncate text-sm font-medium text-ink">{user.name}</p>
        <p className="truncate text-[0.8125rem] text-muted">{user.email}</p>
      </div>
      <MenuSeparator />
      <MenuLink to="/account" icon={<UserRound className="size-4" />}>
        {t('account')}
      </MenuLink>
      <MenuLink to="/account/orders" icon={<Package className="size-4" />}>
        {t('orders')}
      </MenuLink>
      {isShopOwner(user) && (
        <MenuLink to="/owner" icon={<LayoutDashboard className="size-4" />}>
          {t('ownerPanel')}
        </MenuLink>
      )}
      {isAdmin(user) && (
        <MenuLink to={ADMIN_URL} external icon={<ExternalLink className="size-4" />}>
          {t('adminPanel')}
        </MenuLink>
      )}
      <MenuSeparator />
      <MenuButton onClick={signOut} icon={<LogOut className="size-4" />}>
        {t('signOut')}
      </MenuButton>
    </Menu>
  );
}
