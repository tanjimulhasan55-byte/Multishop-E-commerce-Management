import { Badge, type BadgeTone } from '@/components/ui';
import type { OrderStatus, PaymentMethod, PaymentStatus } from '@/types/models';

export const ORDER_STATUSES: OrderStatus[] = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

const orderTone: Record<OrderStatus, BadgeTone> = {
  pending: 'warning',
  processing: 'info',
  shipped: 'brand',
  delivered: 'success',
  cancelled: 'neutral',
};

const paymentTone: Record<PaymentStatus, BadgeTone> = {
  unpaid: 'neutral',
  pending: 'warning',
  paid: 'success',
  failed: 'danger',
  cancelled: 'neutral',
  refunded: 'info',
};

export const capitalize = (s: string) => s.charAt(0).toUpperCase() + s.slice(1);

export const paymentMethodLabel = (m: PaymentMethod): string => (m === 'cod' ? 'Cash on delivery' : 'SSLCommerz');

export function OrderStatusBadge({ status }: { status: OrderStatus }) {
  return (
    <Badge tone={orderTone[status] ?? 'neutral'} dot>
      {capitalize(status)}
    </Badge>
  );
}

export function PaymentStatusBadge({ status }: { status: PaymentStatus }) {
  return <Badge tone={paymentTone[status] ?? 'neutral'}>{capitalize(status)}</Badge>;
}
