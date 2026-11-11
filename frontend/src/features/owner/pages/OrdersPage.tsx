import { useState } from 'react';
import toast from 'react-hot-toast';
import { Receipt } from 'lucide-react';
import { errorMessage } from '@/api/errors';
import { useOwnerOrders, useUpdateOrderStatus } from '@/api/owner';
import {
  Alert,
  Card,
  Drawer,
  EmptyState,
  PageHeader,
  Pagination,
  Select,
  TableShell,
  TableSkeletonRows,
  Tabs,
  Td,
  Th,
  THead,
  Tr,
  useConfirm,
} from '@/components/ui';
import { capitalize, ORDER_STATUSES, OrderStatusBadge, PaymentStatusBadge, paymentMethodLabel } from '@/features/orders/status';
import { useSearchParamState } from '@/hooks/useSearchParamState';
import { formatDate, formatDateTime, formatMoney } from '@/lib/format';
import type { Order, OrderStatus } from '@/types/models';
import { useCurrentShop } from '../useCurrentShop';

const KEYS = ['status', 'scope', 'page'] as const;

function StatusSelect({ order }: { order: Order }) {
  const update = useUpdateOrderStatus();
  const confirm = useConfirm();

  if (order.next_statuses.length === 0) return <OrderStatusBadge status={order.status} />;

  const change = async (status: OrderStatus) => {
    if (status === 'cancelled') {
      const ok = await confirm({
        title: `Cancel order ${order.number}?`,
        description: 'The customer will see this order as cancelled. This cannot be undone.',
        confirmLabel: 'Cancel order',
        cancelLabel: 'Keep order',
      });
      if (!ok) return;
    }
    update.mutate(
      { number: order.number, status },
      {
        onSuccess: () => toast.success(`Order ${order.number} marked ${status}`),
        onError: (e) => toast.error(errorMessage(e)),
      },
    );
  };

  return (
    <div className="flex items-center gap-2">
      <OrderStatusBadge status={order.status} />
      <label htmlFor={`status-${order.number}`} className="sr-only">
        Change status of {order.number}
      </label>
      <Select
        id={`status-${order.number}`}
        value=""
        disabled={update.isPending}
        onChange={(e) => e.target.value && change(e.target.value as OrderStatus)}
        className="h-8 w-32 text-[0.8125rem]"
      >
        <option value="">Move to…</option>
        {order.next_statuses.map((s) => (
          <option key={s} value={s}>
            {capitalize(s)}
          </option>
        ))}
      </Select>
    </div>
  );
}

function OrderDrawer({ order, onClose }: { order: Order | null; onClose: () => void }) {
  return (
    <Drawer open={!!order} onClose={onClose} title={order ? `Order ${order.number}` : ''} description={order ? formatDateTime(order.placed_at) : undefined}>
      {order && (
        <div className="space-y-6">
          <div className="flex flex-wrap items-center gap-2">
            <StatusSelect order={order} />
            <PaymentStatusBadge status={order.payment_status} />
          </div>
          <section>
            <h3 className="text-xs font-semibold tracking-wider text-muted uppercase">Customer</h3>
            <p className="mt-2 text-sm font-medium">{order.customer_name}</p>
            <p className="text-sm text-ink-soft">{order.customer_phone}</p>
            <p className="mt-1 text-sm whitespace-pre-line text-ink-soft">{order.shipping_address}</p>
            {order.notes && <p className="mt-2 rounded-lg bg-canvas p-3 text-sm text-ink-soft">“{order.notes}”</p>}
          </section>
          <section>
            <h3 className="text-xs font-semibold tracking-wider text-muted uppercase">Items</h3>
            <ul className="mt-2 divide-y divide-line rounded-xl border border-line">
              {order.items.map((i) => (
                <li key={i.variant_id} className="flex justify-between gap-3 px-4 py-3 text-sm">
                  <div className="min-w-0">
                    <p className="font-medium">{i.product_name}</p>
                    <p className="text-[0.8125rem] text-muted">
                      {i.variant_label && `${i.variant_label} · `}
                      <span className="font-mono text-xs">{i.sku}</span> · {i.quantity} × {formatMoney(i.unit_price)}
                    </p>
                  </div>
                  <span className="font-medium tabular-nums">{formatMoney(i.line_total)}</span>
                </li>
              ))}
            </ul>
            <dl className="mt-3 space-y-1.5 text-sm">
              <div className="flex justify-between"><dt className="text-muted">Subtotal</dt><dd className="tabular-nums">{formatMoney(order.subtotal)}</dd></div>
              <div className="flex justify-between"><dt className="text-muted">Shipping</dt><dd className="tabular-nums">{formatMoney(order.shipping_total)}</dd></div>
              <div className="flex justify-between font-semibold"><dt>Total</dt><dd className="tabular-nums">{formatMoney(order.total)}</dd></div>
              <div className="flex justify-between"><dt className="text-muted">Payment</dt><dd>{paymentMethodLabel(order.payment_method)}</dd></div>
            </dl>
          </section>
        </div>
      )}
    </Drawer>
  );
}

export default function OwnerOrdersPage() {
  const { shop, shops } = useCurrentShop();
  const { values, update, page, setPage } = useSearchParamState(KEYS);
  const [viewing, setViewing] = useState<string | null>(null);
  const allShops = values.scope === 'all';
  const status = (values.status || 'all') as OrderStatus | 'all';

  const query = useOwnerOrders({
    shop: allShops ? undefined : shop?.slug,
    status: status === 'all' ? undefined : status,
    page,
  });
  const items = query.data?.items;
  const viewed = items?.find((o) => o.number === viewing) ?? null;

  return (
    <div className="space-y-6">
      <PageHeader
        eyebrow={allShops ? 'All shops' : shop?.name}
        title="Orders"
        description="Move orders through fulfilment. Only valid next steps are offered."
        actions={
          shops.length > 1 ? (
            <Select value={values.scope} onChange={(e) => update({ scope: e.target.value })} aria-label="Shop scope" className="w-44">
              <option value="">Current shop</option>
              <option value="all">All my shops</option>
            </Select>
          ) : undefined
        }
      />

      <Card className="overflow-hidden">
        <div className="px-5 pt-3 sm:px-6">
          <Tabs<OrderStatus | 'all'>
            label="Filter by status"
            value={status}
            onChange={(s) => update({ status: s === 'all' ? '' : s })}
            items={[{ value: 'all', label: 'All' }, ...ORDER_STATUSES.map((s) => ({ value: s, label: capitalize(s) }))]}
            className="border-b-0"
          />
        </div>

        {query.isError ? (
          <div className="border-t border-line p-6">
            <Alert tone="danger">{query.error.message}</Alert>
          </div>
        ) : items && items.length === 0 ? (
          <div className="border-t border-line">
            <EmptyState icon={Receipt} title="No orders here" description={status === 'all' ? 'New orders will appear as customers check out.' : `No ${status} orders right now.`} />
          </div>
        ) : (
          <div className="border-t border-line">
            <TableShell>
              <THead>
                <tr>
                  <Th>Order</Th>
                  <Th>Customer</Th>
                  <Th>Total</Th>
                  <Th>Payment</Th>
                  <Th>Status</Th>
                </tr>
              </THead>
              <tbody className={query.isPlaceholderData ? 'opacity-60' : undefined}>
                {!items ? (
                  <TableSkeletonRows cols={5} />
                ) : (
                  items.map((o) => (
                    <Tr key={o.number}>
                      <Td>
                        <button type="button" onClick={() => setViewing(o.number)} className="font-mono text-[0.8125rem] font-semibold text-ink hover:text-brand-700">
                          {o.number}
                        </button>
                        <p className="text-xs text-muted">
                          {formatDate(o.placed_at)}
                          {allShops && ` · ${o.shop_name}`}
                        </p>
                      </Td>
                      <Td>
                        <p className="max-w-48 truncate font-medium">{o.customer_name}</p>
                        <p className="text-xs text-muted">{o.customer_phone}</p>
                      </Td>
                      <Td className="tabular-nums">
                        {formatMoney(o.total)}
                        <p className="text-xs text-muted">{o.items.reduce((n, i) => n + i.quantity, 0)} items</p>
                      </Td>
                      <Td>
                        <PaymentStatusBadge status={o.payment_status} />
                        <p className="mt-0.5 text-xs text-muted">{paymentMethodLabel(o.payment_method)}</p>
                      </Td>
                      <Td>
                        <StatusSelect order={o} />
                      </Td>
                    </Tr>
                  ))
                )}
              </tbody>
            </TableShell>
          </div>
        )}

        {query.data && query.data.meta.total > 0 && (
          <div className="border-t border-line px-5 py-3.5 sm:px-6">
            <Pagination meta={query.data.meta} onPageChange={setPage} />
          </div>
        )}
      </Card>

      <OrderDrawer order={viewed} onClose={() => setViewing(null)} />
    </div>
  );
}
