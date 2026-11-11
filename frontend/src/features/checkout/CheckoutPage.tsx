import { useState, type FormEvent } from 'react';
import { Link, Navigate, useNavigate, useParams } from 'react-router-dom';
import toast from 'react-hot-toast';
import { ArrowLeft, Banknote, CreditCard, Lock, Store } from 'lucide-react';
import { accountApi, usePlaceOrder } from '@/api/account';
import { errorMessage } from '@/api/errors';
import { useSettings } from '@/api/storefront';
import { Alert, Button, Card, Field, ImageBox, Input, Skeleton, Textarea } from '@/components/ui';
import { groupByShop, useCartStore, type CartGroup } from '@/features/cart/store';
import { useFormErrors } from '@/hooks/useFormErrors';
import { cn } from '@/lib/cn';
import { formatMoney } from '@/lib/format';
import { useAuthStore } from '@/stores/auth';
import type { PaymentMethod } from '@/types/models';

const PHONE_RE = /^\+?[0-9]{10,15}$/;

const methodIcons: Record<PaymentMethod, typeof Banknote> = { cod: Banknote, sslcommerz: CreditCard };
const methodHints: Record<PaymentMethod, string> = {
  cod: 'Pay in cash when your order arrives.',
  sslcommerz: 'Cards, bKash, Nagad and more via SSLCommerz.',
};

export default function CheckoutPage() {
  const { shopId } = useParams();
  const user = useAuthStore((s) => s.user);
  const items = useCartStore((s) => s.items);
  const clearShop = useCartStore((s) => s.clearShop);
  const liveGroup = groupByShop(items).find((g) => String(g.shopId) === shopId);
  // Frozen once the order is placed, so clearing the cart doesn't bounce us to /cart.
  const [placedGroup, setPlacedGroup] = useState<CartGroup | null>(null);
  const group = placedGroup ?? liveGroup;
  const settings = useSettings();
  const placeOrder = usePlaceOrder();
  const navigate = useNavigate();
  const { errorFor, errors, handleError, clearError, setErrors } = useFormErrors();

  const [form, setForm] = useState({
    customer_name: user?.name ?? '',
    customer_phone: user?.phone ?? '',
    shipping_address: '',
    notes: '',
    payment_method: 'cod' as PaymentMethod,
  });
  const [redirecting, setRedirecting] = useState(false);

  if (!group) return <Navigate to="/cart" replace />;

  const set = <K extends keyof typeof form>(key: K, value: (typeof form)[K]) => {
    setForm((f) => ({ ...f, [key]: value }));
    clearError(key);
  };

  const validate = (): boolean => {
    const next: Record<string, string> = {};
    if (!form.customer_name.trim()) next.customer_name = 'Please enter the recipient’s name.';
    if (!PHONE_RE.test(form.customer_phone.trim())) next.customer_phone = 'Enter a valid phone number (10–15 digits, optional +).';
    if (form.shipping_address.trim().length < 10) next.shipping_address = 'Please enter a full address (at least 10 characters).';
    setErrors(next);
    return Object.keys(next).length === 0;
  };

  const submit = async (e: FormEvent) => {
    e.preventDefault();
    if (!validate()) return;
    try {
      const order = await placeOrder.mutateAsync({
        shop_id: group.shopId,
        items: group.items.map((i) => ({ variant_id: i.variantId, quantity: i.quantity })),
        customer_name: form.customer_name.trim(),
        customer_phone: form.customer_phone.trim(),
        shipping_address: form.shipping_address.trim(),
        notes: form.notes.trim() || undefined,
        payment_method: form.payment_method,
      });
      setPlacedGroup(group);
      clearShop(group.shopId);
      if (order.payment_method === 'sslcommerz') {
        setRedirecting(true);
        try {
          window.location.assign(await accountApi.payWithSslCommerz(order.number));
          return;
        } catch (err) {
          setRedirecting(false);
          toast.error(`Order placed, but payment could not start: ${errorMessage(err)}`);
        }
      } else {
        toast.success(`Order ${order.number} placed`);
      }
      navigate(`/account/orders/${order.number}?placed=1`, { replace: true });
    } catch (err) {
      handleError(err);
    }
  };

  const itemErrors = Object.entries(errors).filter(([k]) => k.startsWith('items'));
  const itemError = (index: number) =>
    Object.entries(errors).find(([k]) => k.startsWith(`items.${index}.`))?.[1];
  const methods = settings.data?.payment_methods ?? [];
  const busy = placeOrder.isPending || redirecting;

  return (
    <div className="container-page py-8 sm:py-12">
      <Link to="/cart" className="inline-flex items-center gap-1.5 text-sm text-muted hover:text-ink">
        <ArrowLeft className="size-4" /> Back to cart
      </Link>
      <h1 className="mt-4 text-2xl font-semibold tracking-tight sm:text-[1.75rem]">Checkout</h1>
      <p className="mt-1 flex items-center gap-1.5 text-sm text-muted">
        <Store className="size-4" aria-hidden /> Ordering from <span className="font-medium text-ink">{group.shopName}</span>
      </p>

      <form onSubmit={submit} noValidate className="mt-8 grid gap-8 lg:grid-cols-[1fr_24rem] lg:items-start">
        <div className="space-y-6">
          <Card className="p-5 sm:p-6">
            <h2 className="text-[0.9375rem] font-semibold">Delivery details</h2>
            <div className="mt-5 grid gap-5 sm:grid-cols-2">
              <Field label="Full name" required error={errorFor('customer_name')}>
                <Input value={form.customer_name} onChange={(e) => set('customer_name', e.target.value)} autoComplete="name" />
              </Field>
              <Field label="Phone" required error={errorFor('customer_phone')} hint="We’ll call to confirm delivery.">
                <Input
                  type="tel"
                  value={form.customer_phone}
                  onChange={(e) => set('customer_phone', e.target.value)}
                  autoComplete="tel"
                  placeholder="01XXXXXXXXX"
                />
              </Field>
              <Field label="Shipping address" required error={errorFor('shipping_address')} className="sm:col-span-2">
                <Textarea
                  rows={3}
                  value={form.shipping_address}
                  onChange={(e) => set('shipping_address', e.target.value)}
                  autoComplete="street-address"
                  placeholder="House, road, area, city"
                />
              </Field>
              <Field label="Order notes" optional error={errorFor('notes')} className="sm:col-span-2">
                <Textarea rows={2} value={form.notes} onChange={(e) => set('notes', e.target.value)} placeholder="Delivery instructions, landmarks…" />
              </Field>
            </div>
          </Card>

          <Card className="p-5 sm:p-6">
            <fieldset>
              <legend className="text-[0.9375rem] font-semibold">Payment method</legend>
              <div className="mt-4 grid gap-3 sm:grid-cols-2">
                {settings.isLoading &&
                  Array.from({ length: 2 }, (_, i) => <Skeleton key={i} className="h-[4.5rem] rounded-xl" />)}
                {methods.map((m) => {
                  const Icon = methodIcons[m.value] ?? Banknote;
                  const checked = form.payment_method === m.value;
                  return (
                    <label
                      key={m.value}
                      className={cn(
                        'flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition-[border-color,box-shadow,background-color] duration-150',
                        checked ? 'border-brand-500 bg-brand-50/50 shadow-[var(--shadow-focus)]' : 'border-line-strong hover:border-faint',
                      )}
                    >
                      <input
                        type="radio"
                        name="payment_method"
                        value={m.value}
                        checked={checked}
                        onChange={() => set('payment_method', m.value)}
                        className="mt-1 accent-brand-600"
                      />
                      <span className="flex-1">
                        <span className="flex items-center gap-2 text-sm font-medium">
                          <Icon className="size-4 text-brand-600" aria-hidden /> {m.label}
                        </span>
                        <span className="mt-0.5 block text-[0.8125rem] text-muted">{methodHints[m.value] ?? ''}</span>
                      </span>
                    </label>
                  );
                })}
              </div>
              {errorFor('payment_method') && <p className="mt-2 text-[0.8125rem] text-danger-600">{errorFor('payment_method')}</p>}
            </fieldset>
          </Card>
        </div>

        <aside className="lg:sticky lg:top-24">
          <Card className="overflow-hidden">
            <div className="border-b border-line px-5 py-4 sm:px-6">
              <h2 className="text-[0.9375rem] font-semibold">Order summary</h2>
            </div>
            <ul className="max-h-80 divide-y divide-line overflow-y-auto px-5 sm:px-6">
              {group.items.map((item, i) => (
                <li key={item.variantId} className="flex gap-3 py-3.5">
                  <div className="relative shrink-0">
                    <ImageBox image={item.image} alt={item.title} variant="thumb" className="size-14 rounded-lg border border-line" />
                    <span className="absolute -top-1.5 -right-1.5 flex size-5 items-center justify-center rounded-full bg-ink text-[0.6875rem] font-semibold text-white">
                      {item.quantity}
                    </span>
                  </div>
                  <div className="min-w-0 flex-1">
                    <p className="line-clamp-1 text-sm font-medium">{item.title}</p>
                    {item.variantLabel && <p className="text-[0.8125rem] text-muted">{item.variantLabel}</p>}
                    {itemError(i) && <p className="text-[0.8125rem] text-danger-600">{itemError(i)}</p>}
                  </div>
                  <p className="text-sm font-medium tabular-nums">{formatMoney(Number(item.price) * item.quantity)}</p>
                </li>
              ))}
            </ul>
            <dl className="space-y-2 border-t border-line px-5 py-4 text-sm sm:px-6">
              <div className="flex justify-between">
                <dt className="text-muted">Subtotal</dt>
                <dd className="tabular-nums">{formatMoney(group.subtotal)}</dd>
              </div>
              <div className="flex justify-between">
                <dt className="text-muted">Shipping</dt>
                <dd className="text-muted">Confirmed on order</dd>
              </div>
              <div className="flex justify-between border-t border-line pt-3 text-base font-semibold">
                <dt>Total</dt>
                <dd className="tabular-nums">{formatMoney(group.subtotal)}</dd>
              </div>
            </dl>
            <div className="space-y-3 border-t border-line bg-canvas px-5 py-4 sm:px-6">
              {itemErrors.length > 0 && !group.items.some((_, i) => itemError(i)) && (
                <Alert tone="danger">{itemErrors[0][1]}</Alert>
              )}
              {errorFor('shop_id') && <Alert tone="danger">{errorFor('shop_id')}</Alert>}
              <Button type="submit" block size="lg" loading={busy} leadingIcon={<Lock className="size-4" />}>
                {form.payment_method === 'sslcommerz' ? 'Place order & pay' : 'Place order'}
              </Button>
              <p className="text-center text-xs text-muted">Prices and stock are re-checked when you place the order.</p>
            </div>
          </Card>
        </aside>
      </form>
    </div>
  );
}
