import { Check, ChevronsUpDown } from 'lucide-react';
import toast from 'react-hot-toast';
import { Skeleton } from '@/components/ui';
import { Menu, MenuButton } from '@/components/ui/Menu';
import { ShopLogo } from '@/features/storefront/components/ShopCard';
import { useCurrentShop } from '../useCurrentShop';

export function ShopSwitcher() {
  const { shop, shops, isLoading, setShopSlug } = useCurrentShop();

  if (isLoading) return <Skeleton className="h-10 w-full rounded-xl" />;
  if (!shop) return <p className="px-2 text-[0.8125rem] text-muted">No shops assigned yet.</p>;

  return (
    <Menu
      align="left"
      className="w-full"
      trigger={(props) => (
        <button
          type="button"
          {...props}
          aria-label={`Current shop: ${shop.name}. Switch shop`}
          className="flex w-full items-center gap-2.5 rounded-xl border border-line bg-surface p-1.5 pr-2.5 text-left shadow-xs transition-colors hover:border-line-strong"
        >
          <ShopLogo shop={shop} className="size-8 rounded-lg text-sm" />
          <span className="min-w-0 flex-1">
            <span className="block truncate text-sm font-medium">{shop.name}</span>
            <span className="block truncate text-xs text-muted">{shops.length > 1 ? `${shops.length} shops` : 'Your shop'}</span>
          </span>
          <ChevronsUpDown className="size-4 shrink-0 text-muted" aria-hidden />
        </button>
      )}
    >
      {shops.map((s) => (
        <MenuButton
          key={s.id}
          onClick={() => {
            setShopSlug(s.slug);
            if (s.slug !== shop.slug) toast.success(`Now managing ${s.name}`);
          }}
          icon={<ShopLogo shop={s} className="size-6 rounded-md text-xs" />}
        >
          <span className="flex-1 truncate">{s.name}</span>
          {s.slug === shop.slug && <Check className="size-4 text-brand-600" aria-hidden />}
        </MenuButton>
      ))}
    </Menu>
  );
}
