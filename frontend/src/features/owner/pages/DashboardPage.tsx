import { Link } from 'react-router-dom';
import { AlertTriangle, ArrowRight, Boxes, Clock, ListChecks, Receipt, Store, Wallet } from 'lucide-react';
import { useOwnerDashboard, useOwnerOrders } from '@/api/owner';
import { Alert, ButtonLink, Card, CardHeader, EmptyState, PageHeader, Skeleton, StatCard } from '@/components/ui';
import { OrderStatusBadge } from '@/features/orders/status';
import { formatDate, formatMoney, formatNumber } from '@/lib/format';
import { useAuthStore } from '@/stores/auth';

export default function DashboardPage() {
  const stats = useOwnerDashboard();
  const recent = useOwnerOrders({ page: 1 });
  const user = useAuthStore((s) => s.user);
  const s = stats.data;
  const loading = stats.isLoading;

  return (
    <div className="space-y-8">
      <PageHeader
        eyebrow="Overview"
        title={`Good to see you, ${user?.name.split(' ')[0] ?? 'there'}`}
        description="Here’s how your shops are doing."
        actions={
          <ButtonLink to="/owner/catalog" leadingIcon={<ListChecks className="size-4" />}>
            List a product
          </ButtonLink>
        }
      />

      {stats.isError && <Alert tone="danger">{stats.error.message}</Alert>}

      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard label="Revenue" icon={Wallet} tone="success" loading={loading} value={s && formatMoney(s.revenue)} hint="Across your shops" />
        <StatCard
          label="Orders"
          icon={Receipt}
          loading={loading}
          value={s && formatNumber(s.orders)}
          hint={s && (s.pending_orders > 0 ? <span className="text-warning-700">{s.pending_orders} awaiting action</span> : 'All caught up')}
        />
        <StatCard
          label="Active listings"
          icon={ListChecks}
          tone="info"
          loading={loading}
          value={s && `${formatNumber(s.active_listings)} / ${formatNumber(s.listings)}`}
          hint={s && `${formatNumber(s.shops)} shop${s.shops === 1 ? '' : 's'}`}
        />
        <StatCard
          label="Low stock"
          icon={AlertTriangle}
          tone="warning"
          loading={loading}
          value={s && formatNumber(s.low_stock)}
          hint={
            <Link to="/owner/listings?low_stock=1" className="font-medium text-brand-600 hover:text-brand-700">
              Review listings →
            </Link>
          }
        />
      </div>

      <div className="grid gap-6 lg:grid-cols-[1fr_20rem]">
        <Card className="overflow-hidden">
          <CardHeader
            title="Recent orders"
            actions={
              <Link to="/owner/orders" className="inline-flex items-center gap-1 text-[0.8125rem] font-medium text-brand-600 hover:text-brand-700">
                View all <ArrowRight className="size-3.5" />
              </Link>
            }
          />
          {recent.isLoading ? (
            <div className="space-y-3 p-6">
              {Array.from({ length: 4 }, (_, i) => (
                <Skeleton key={i} className="h-10" />
              ))}
            </div>
          ) : recent.data?.items.length === 0 ? (
            <EmptyState compact icon={Receipt} title="No orders yet" description="Orders for your shops will appear here." />
          ) : (
            <ul className="divide-y divide-line">
              {recent.data?.items.slice(0, 6).map((o) => (
                <li key={o.number} className="flex items-center gap-4 px-5 py-3 sm:px-6">
                  <div className="min-w-0 flex-1">
                    <p className="font-mono text-[0.8125rem] font-medium">{o.number}</p>
                    <p className="truncate text-[0.8125rem] text-muted">
                      {o.customer_name} · {o.shop_name} · {formatDate(o.placed_at)}
                    </p>
                  </div>
                  <OrderStatusBadge status={o.status} />
                  <span className="hidden w-28 text-right text-sm font-medium tabular-nums sm:block">{formatMoney(o.total)}</span>
                </li>
              ))}
            </ul>
          )}
        </Card>

        <Card className="p-5 sm:p-6">
          <h2 className="text-[0.9375rem] font-semibold">Quick actions</h2>
          <ul className="mt-4 space-y-2">
            {[
              { to: '/owner/products', icon: Boxes, label: 'Create a product', text: `${s ? formatNumber(s.my_products) : '–'} created by you` },
              { to: '/owner/orders?status=pending', icon: Clock, label: 'Pending orders', text: `${s ? formatNumber(s.pending_orders) : '–'} to confirm` },
              { to: '/owner/shops', icon: Store, label: 'Switch shop', text: 'Manage another shop' },
            ].map(({ to, icon: Icon, label, text }) => (
              <li key={to}>
                <Link to={to} className="group flex items-center gap-3 rounded-xl border border-line p-3 transition-colors hover:border-line-strong hover:bg-canvas">
                  <span className="flex size-9 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                    <Icon className="size-4" aria-hidden />
                  </span>
                  <span className="min-w-0 flex-1">
                    <span className="block text-sm font-medium">{label}</span>
                    <span className="block text-[0.8125rem] text-muted">{text}</span>
                  </span>
                  <ArrowRight className="size-4 text-faint transition-transform group-hover:translate-x-0.5" aria-hidden />
                </Link>
              </li>
            ))}
          </ul>
        </Card>
      </div>
    </div>
  );
}
