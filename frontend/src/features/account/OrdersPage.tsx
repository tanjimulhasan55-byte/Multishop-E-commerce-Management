import { Link } from 'react-router-dom';
import { ChevronRight, Package } from 'lucide-react';
import { useMyOrders } from '@/api/account';
import { Alert, ButtonLink, Card, CardHeader, EmptyState, Pagination, Skeleton } from '@/components/ui';
import { OrderStatusBadge, PaymentStatusBadge } from '@/features/orders/status';
import { useSearchParamState } from '@/hooks/useSearchParamState';
import { formatDate, formatMoney, pluralize } from '@/lib/format';

const KEYS = ['page'] as const;

export default function OrdersPage() {
  const { page, setPage } = useSearchParamState(KEYS);
  const query = useMyOrders(page);

  return (
    <Card className="overflow-hidden">
      <CardHeader title="Orders" description="Every order you’ve placed, newest first." />
      {query.isError ? (
        <div className="p-6">
          <Alert tone="danger">{query.error.message}</Alert>
        </div>
      ) : query.isLoading ? (
        <ul className="divide-y divide-line">
          {Array.from({ length: 4 }, (_, i) => (
            <li key={i} className="flex items-center gap-4 px-5 py-4 sm:px-6">
              <div className="flex-1 space-y-2">
                <Skeleton className="h-4 w-40" />
                <Skeleton className="h-3 w-56" />
              </div>
              <Skeleton className="h-5 w-20" />
            </li>
          ))}
        </ul>
      ) : query.data?.items.length === 0 ? (
        <EmptyState
          icon={Package}
          title="No orders yet"
          description="When you place an order it will show up here."
          action={<ButtonLink to="/products">Start shopping</ButtonLink>}
        />
      ) : (
        <ul className="divide-y divide-line">
          {query.data?.items.map((order) => (
            <li key={order.number}>
              <Link
                to={`/account/orders/${order.number}`}
                className="group flex items-center gap-4 px-5 py-4 transition-colors duration-150 hover:bg-canvas sm:px-6"
              >
                <div className="min-w-0 flex-1">
                  <div className="flex flex-wrap items-center gap-2">
                    <span className="font-mono text-[0.8125rem] font-semibold text-ink">{order.number}</span>
                    <OrderStatusBadge status={order.status} />
                    <PaymentStatusBadge status={order.payment_status} />
                  </div>
                  <p className="mt-1 truncate text-[0.8125rem] text-muted">
                    {order.shop_name} · {formatDate(order.placed_at)} · {pluralize(order.items.reduce((n, i) => n + i.quantity, 0), 'item')}
                  </p>
                </div>
                <span className="text-sm font-semibold tabular-nums">{formatMoney(order.total)}</span>
                <ChevronRight className="size-4 text-faint transition-transform group-hover:translate-x-0.5" aria-hidden />
              </Link>
            </li>
          ))}
        </ul>
      )}
      {query.data && query.data.meta.last_page > 1 && (
        <div className="border-t border-line px-5 py-4 sm:px-6">
          <Pagination meta={query.data.meta} onPageChange={setPage} />
        </div>
      )}
    </Card>
  );
}
