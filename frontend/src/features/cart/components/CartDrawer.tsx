import { useEffect } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { ShoppingBag, Store } from 'lucide-react';
import { ButtonLink, Drawer, EmptyState } from '@/components/ui';
import { formatMoney } from '@/lib/format';
import { groupByShop, useCartDrawer, useCartStore } from '../store';
import { CartLine } from './CartLine';

export function CartDrawer() {
  const { open, setOpen } = useCartDrawer();
  const items = useCartStore((s) => s.items);
  const groups = groupByShop(items);
  const total = groups.reduce((n, g) => n + g.subtotal, 0);
  const location = useLocation();
  const close = () => setOpen(false);

  useEffect(() => setOpen(false), [location.pathname, setOpen]);

  return (
    <Drawer
      open={open}
      onClose={close}
      title="Your cart"
      description={items.length ? `${groups.length} shop${groups.length === 1 ? '' : 's'} · checkout is per shop` : undefined}
      footer={
        items.length ? (
          <div className="w-full space-y-3">
            <div className="flex items-baseline justify-between">
              <span className="text-sm text-muted">Subtotal</span>
              <span className="text-lg font-semibold tabular-nums">{formatMoney(total)}</span>
            </div>
            <ButtonLink to="/cart" block size="lg">
              Review & checkout
            </ButtonLink>
          </div>
        ) : undefined
      }
    >
      {items.length === 0 ? (
        <EmptyState
          compact
          icon={ShoppingBag}
          title="Your cart is empty"
          description="Find something you love — it will wait for you here."
          action={
            <ButtonLink to="/products" onClick={close}>
              Start shopping
            </ButtonLink>
          }
        />
      ) : (
        <div className="space-y-6">
          {groups.map((g) => (
            <section key={g.shopId} aria-labelledby={`drawer-shop-${g.shopId}`}>
              <div className="flex items-center gap-2 text-[0.8125rem] font-medium text-ink-soft">
                <Store className="size-4 text-muted" aria-hidden />
                {g.shopSlug ? (
                  <Link id={`drawer-shop-${g.shopId}`} to={`/shops/${g.shopSlug}`} onClick={close} className="hover:text-brand-700">
                    {g.shopName}
                  </Link>
                ) : (
                  <span id={`drawer-shop-${g.shopId}`}>{g.shopName}</span>
                )}
              </div>
              <ul className="divide-y divide-line">
                {g.items.map((item) => (
                  <CartLine key={item.variantId} item={item} compact onNavigate={close} />
                ))}
              </ul>
            </section>
          ))}
        </div>
      )}
    </Drawer>
  );
}
