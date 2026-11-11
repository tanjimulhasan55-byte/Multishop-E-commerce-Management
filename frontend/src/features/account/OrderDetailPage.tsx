import { useEffect, useRef } from 'react';
import { Link, useParams, useSearchParams } from 'react-router-dom';
import toast from 'react-hot-toast';
import { useQueryClient } from '@tanstack/react-query';
import { ArrowLeft, Check, CreditCard, MapPin, Package, Phone, Store, X } from 'lucide-react';
import { accountKeys, useMyOrder, usePayOnline } from '@/api/account';
import { errorMessage } from '@/api/errors';
import { Alert, Button, ButtonLink, Card, CardHeader, EmptyState, Skeleton } from '@/components/ui';
import { OrderStatusBadge, PaymentStatusBadge, paymentMethodLabel } from '@/features/orders/status';
import { cn } from '@/lib/cn';
import { formatDateTime, formatMoney } from '@/lib/format';
import type { Order, OrderStatus } from '@/types/models';

const FLOW: OrderStatus[] = ['pending', 'processing', 'shipped', 'delivered'];
const FLOW_LABEL: Record<OrderStatus, string> = {
  pending: 'Order placed',
  processing: 'Processing',
  shipped: 'Shipped',
  delivered: 'Delivered',
  cancelled: 'Cancelled',
};

function StatusTimeline({ status }: { status: OrderStatus }) {
  if (status === 'cancelled') {
    return (
      <div className="flex items-center gap-3 rounded-xl bg-subtle px-4 py-3 text-sm text-ink-soft">
        <span className="flex size-8 items-center justify-center rounded-full bg-surface ring-1 ring-line">
          <X className="size-4 text-danger-600" aria-hidden />
        </span>
        This order was cancelled.
      </div>
    );
  }
  const current = FLOW.indexOf(status);
  return (
    <ol className="grid grid-cols-4" aria-label="Order progress">
      {FLOW.map((step, i) => {
        const done = i <= current;
        return (
          <li key={step} className="relative flex flex-col items-center text-center" aria-current={i === current ? 'step' : undefined}>
            {i > 0 && (
              <span
                aria-hidden
                className={cn('absolute top-4 right-1/2 h-0.5 w-full -translate-y-1/2', i <= current ? 'bg-brand-600' : 'bg-line')}
              />
            )}
            <span
              className={cn(
                'relative z-10 flex size-8 items-center justify-center rounded-full text-xs font-semibold transition-colors',
                done ? 'bg-brand-600 text-white' : 'bg-surface text-muted ring-1 ring-line-strong',
                i === current && 'ring-4 ring-brand-100',
              )}
            >
              {done ? <Check className="size-4" strokeWidth={3} aria-hidden /> : i + 1}
            </span>
            <span className={cn('mt-2 text-xs font-medium sm:text-[0.8125rem]', done ? 'text-ink' : 'text-muted')}>{FLOW_LABEL[step]}</span>
          </li>
        );
      })}
    </ol>
  );
}

function PaymentBanner({ outcome, onDismiss }: { outcome: string; onDismiss: () => void }) {
  if (outcome === 'paid') {
    return (
      <Alert tone="success" title="Payment received" onDismiss={onDismiss}>
        Thank you! Your payment was confirmed and the shop has been notified.
      </Alert>
    );
  }
  if (outcome === 'cancelled') {
    return (
      <Alert tone="warning" title="Payment cancelled" onDismiss={onDismiss}>
        You cancelled the payment. Your order is saved — you can pay any time below.
      </Alert>
    );
  }
  return (
    <Alert tone="danger" title="Payment failed" onDismiss={onDismiss}>
      We couldn’t confirm your payment. No money was taken — please try again.
    </Alert>
  );
}

function OrderSkeleton() {
  return (
    <div className="space-y-6" aria-busy>
      <Skeleton className="h-8 w-64" />
      <Skeleton className="h-28 rounded-2xl" />
      <Skeleton className="h-64 rounded-2xl" />
    </div>
  );
}

function canPayOnline(order: Order): boolean {
  return order.payment_method === 'sslcommerz' && order.payment_status !== 'paid' && order.payment_status !== 'refunded' && order.status !== 'cancelled';
}

export default function OrderDetailPage() {
  const { number } = useParams();
  const [params, setParams] = useSearchParams();
  const query = useMyOrder(number);
  const pay = usePayOnline();
  const qc = useQueryClient();
  const outcome = params.get('payment');
  const placed = params.get('placed') === '1';
  const notified = useRef(false);

  useEffect(() => {
    if (!outcome || notified.current) return;
    notified.current = true;
    if (outcome === 'paid') toast.success('Payment successful');
    else if (outcome === 'cancelled') toast('Payment cancelled');
    else toast.error('Payment failed');
    // The gateway callback changed payment status — refetch.
    if (number) void qc.invalidateQueries({ queryKey: accountKeys.order(number) });
  }, [outcome, number, qc]);

  const dismiss = (key: string) =>
    setParams(
      (p) => {
        const next = new URLSearchParams(p);
        next.delete(key);
        return next;
      },
      { replace: true },
    );

  if (query.isError) {
    return (
      <Card>
        <EmptyState
          icon={Package}
          title="Order not found"
          description={errorMessage(query.error)}
          action={<ButtonLink to="/account/orders">Back to orders</ButtonLink>}
        />
      </Card>
    );
  }

  const order = query.data;

  return (
    <div className="space-y-6">
      <Link to="/account/orders" className="inline-flex items-center gap-1.5 text-sm text-muted hover:text-ink">
        <ArrowLeft className="size-4" /> All orders
      </Link>

      {outcome && <PaymentBanner outcome={outcome} onDismiss={() => dismiss('payment')} />}
      {placed && !outcome && (
        <Alert tone="success" title="Order placed!" onDismiss={() => dismiss('placed')}>
          {order?.shop_name ?? 'The shop'} will confirm your order shortly. We’ll keep this page updated.
        </Alert>
      )}

      {!order ? (
        <OrderSkeleton />
      ) : (
        <>
          <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
              <p className="text-[0.8125rem] text-muted">Placed {formatDateTime(order.placed_at)}</p>
              <h1 className="mt-1 font-mono text-xl font-semibold tracking-tight sm:text-2xl">{order.number}</h1>
              <div className="mt-2 flex flex-wrap gap-2">
                <OrderStatusBadge status={order.status} />
                <PaymentStatusBadge status={order.payment_status} />
              </div>
            </div>
            {canPayOnline(order) && (
              <Button
                size="lg"
                loading={pay.isPending}
                leadingIcon={<CreditCard className="size-4" />}
                onClick={() => pay.mutate(order.number, { onError: (e) => toast.error(errorMessage(e)) })}
              >
                Pay online {formatMoney(order.total)}
              </Button>
            )}
          </div>

          <Card className="p-5 sm:p-6">
            <StatusTimeline status={order.status} />
          </Card>

          <div className="grid gap-6 lg:grid-cols-[1fr_18rem] lg:items-start">
            <Card className="overflow-hidden">
              <CardHeader title="Items" description={<span className="inline-flex items-center gap-1.5"><Store className="size-3.5" aria-hidden /> {order.shop_name}</span>} />
              <ul className="divide-y divide-line">
                {order.items.map((item) => (
                  <li key={item.variant_id} className="flex items-start gap-4 px-5 py-4 sm:px-6">
                    <div className="min-w-0 flex-1">
                      <Link to={`/products/${item.listing_id}`} className="text-sm font-medium hover:text-brand-700">
                        {item.product_name}
                      </Link>
                      <p className="mt-0.5 text-[0.8125rem] text-muted">
                        {item.variant_label && <span>{item.variant_label} · </span>}
                        <span className="font-mono text-xs">{item.sku}</span>
                      </p>
                      <p className="mt-1 text-[0.8125rem] text-muted tabular-nums">
                        {item.quantity} × {formatMoney(item.unit_price)}
                      </p>
                    </div>
                    <span className="text-sm font-semibold tabular-nums">{formatMoney(item.line_total)}</span>
                  </li>
                ))}
              </ul>
              <dl className="space-y-2 border-t border-line bg-canvas px-5 py-4 text-sm sm:px-6">
                <div className="flex justify-between">
                  <dt className="text-muted">Subtotal</dt>
                  <dd className="tabular-nums">{formatMoney(order.subtotal)}</dd>
                </div>
                <div className="flex justify-between">
                  <dt className="text-muted">Shipping</dt>
                  <dd className="tabular-nums">{formatMoney(order.shipping_total)}</dd>
                </div>
                <div className="flex justify-between border-t border-line pt-2.5 text-base font-semibold">
                  <dt>Total</dt>
                  <dd className="tabular-nums">{formatMoney(order.total)}</dd>
                </div>
              </dl>
            </Card>

            <Card className="divide-y divide-line">
              <section className="p-5">
                <h2 className="text-xs font-semibold tracking-wider text-muted uppercase">Delivery</h2>
                <p className="mt-2 text-sm font-medium">{order.customer_name}</p>
                <p className="mt-1 flex items-start gap-2 text-sm text-ink-soft">
                  <MapPin className="mt-0.5 size-4 shrink-0 text-muted" aria-hidden /> {order.shipping_address}
                </p>
                <p className="mt-1 flex items-center gap-2 text-sm text-ink-soft">
                  <Phone className="size-4 shrink-0 text-muted" aria-hidden /> {order.customer_phone}
                </p>
              </section>
              <section className="p-5">
                <h2 className="text-xs font-semibold tracking-wider text-muted uppercase">Payment</h2>
                <p className="mt-2 text-sm">{paymentMethodLabel(order.payment_method)}</p>
              </section>
              {order.notes && (
                <section className="p-5">
                  <h2 className="text-xs font-semibold tracking-wider text-muted uppercase">Notes</h2>
                  <p className="mt-2 text-sm whitespace-pre-line text-ink-soft">{order.notes}</p>
                </section>
              )}
            </Card>
          </div>
        </>
      )}
    </div>
  );
}
