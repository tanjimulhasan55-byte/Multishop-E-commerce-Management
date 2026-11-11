import { Link } from 'react-router-dom';
import { ArrowRight, ShieldCheck, ShoppingBag, Store, Truck } from 'lucide-react';
import { Button, ButtonLink, Card, EmptyState, PageHeader, useConfirm } from '@/components/ui';
import { formatMoney, pluralize } from '@/lib/format';
import { CartLine } from './components/CartLine';
import { groupByShop, useCartStore } from './store';

export default function CartPage() {
  const items = useCartStore((s) => s.items);
  const clearShop = useCartStore((s) => s.clearShop);
  const confirm = useConfirm();
  const groups = groupByShop(items);
  const total = groups.reduce((n, g) => n + g.subtotal, 0);

  if (items.length === 0) {
    return (
      <div className="container-page py-12">
        <Card>
          <EmptyState
            icon={ShoppingBag}
            title="Your cart is empty"
            description="Browse products from independent shops across Bangladesh."
            action={<ButtonLink to="/products">Browse products</ButtonLink>}
          />
        </Card>
      </div>
    );
  }

  return (
    <div className="container-page py-8 sm:py-12">
      <PageHeader
        title="Shopping cart"
        description={`${pluralize(items.reduce((n, i) => n + i.quantity, 0), 'item')} from ${pluralize(groups.length, 'shop')}. Each shop ships separately, so you'll check out one shop at a time.`}
      />

      <div className="mt-8 grid gap-8 lg:grid-cols-[1fr_20rem] lg:items-start">
        <div className="space-y-6">
          {groups.map((g) => (
            <Card key={g.shopId} className="overflow-hidden">
              <div className="flex flex-wrap items-center justify-between gap-3 border-b border-line bg-canvas px-5 py-3.5 sm:px-6">
                <div className="flex items-center gap-2.5">
                  <span className="flex size-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                    <Store className="size-4" aria-hidden />
                  </span>
                  <div>
                    {g.shopSlug ? (
                      <Link to={`/shops/${g.shopSlug}`} className="text-sm font-semibold hover:text-brand-700">
                        {g.shopName}
                      </Link>
                    ) : (
                      <span className="text-sm font-semibold">{g.shopName}</span>
                    )}
                    <p className="text-xs text-muted">{pluralize(g.count, 'item')}</p>
                  </div>
                </div>
                <Button
                  variant="ghost"
                  size="xs"
                  onClick={async () => {
                    const ok = await confirm({
                      title: `Remove all items from ${g.shopName}?`,
                      description: 'This clears this shop’s items from your cart.',
                      confirmLabel: 'Remove items',
                    });
                    if (ok) clearShop(g.shopId);
                  }}
                >
                  Clear
                </Button>
              </div>
              <ul className="divide-y divide-line px-5 sm:px-6">
                {g.items.map((item) => (
                  <CartLine key={item.variantId} item={item} />
                ))}
              </ul>
              <div className="flex flex-col gap-3 border-t border-line px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <p className="text-sm text-muted">
                  Shop subtotal <span className="ml-1 text-base font-semibold text-ink tabular-nums">{formatMoney(g.subtotal)}</span>
                </p>
                <ButtonLink to={`/checkout/${g.shopId}`} trailingIcon={<ArrowRight className="size-4" />}>
                  Checkout this shop
                </ButtonLink>
              </div>
            </Card>
          ))}
        </div>

        <aside className="lg:sticky lg:top-24">
          <Card className="p-5 sm:p-6">
            <h2 className="text-[0.9375rem] font-semibold">Summary</h2>
            <dl className="mt-4 space-y-2.5 text-sm">
              {groups.map((g) => (
                <div key={g.shopId} className="flex justify-between gap-4">
                  <dt className="truncate text-muted">{g.shopName}</dt>
                  <dd className="tabular-nums">{formatMoney(g.subtotal)}</dd>
                </div>
              ))}
              <div className="flex justify-between gap-4">
                <dt className="text-muted">Shipping</dt>
                <dd className="text-success-700">Calculated per order</dd>
              </div>
              <div className="flex justify-between gap-4 border-t border-line pt-3 text-base font-semibold">
                <dt>Total</dt>
                <dd className="tabular-nums">{formatMoney(total)}</dd>
              </div>
            </dl>
            <ul className="mt-6 space-y-3 border-t border-line pt-5 text-[0.8125rem] text-muted">
              <li className="flex gap-2.5">
                <ShieldCheck className="size-4 shrink-0 text-success-600" aria-hidden /> Secure checkout, prices verified at order time
              </li>
              <li className="flex gap-2.5">
                <Truck className="size-4 shrink-0 text-brand-600" aria-hidden /> Cash on delivery available nationwide
              </li>
            </ul>
          </Card>
        </aside>
      </div>
    </div>
  );
}
