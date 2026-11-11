import { Link } from 'react-router-dom';
import { Trash2 } from 'lucide-react';
import { Button, ImageBox, Price, QuantityStepper } from '@/components/ui';
import { cn } from '@/lib/cn';
import { formatMoney, toNumber } from '@/lib/format';
import { MAX_QTY, useCartStore, type CartItem } from '../store';

interface CartLineProps {
  item: CartItem;
  compact?: boolean;
  onNavigate?: () => void;
  error?: string;
}

export function CartLine({ item, compact, onNavigate, error }: CartLineProps) {
  const setQuantity = useCartStore((s) => s.setQuantity);
  const remove = useCartStore((s) => s.remove);
  const lineTotal = toNumber(item.price) * item.quantity;

  return (
    <li className="flex gap-4 py-4">
      <Link to={`/products/${item.listingId}`} onClick={onNavigate} className="shrink-0 rounded-xl">
        <ImageBox
          image={item.image}
          alt={item.title}
          variant="thumb"
          className={cn('rounded-xl border border-line', compact ? 'size-16' : 'size-20 sm:size-24')}
        />
      </Link>
      <div className="flex min-w-0 flex-1 flex-col">
        <div className="flex items-start justify-between gap-3">
          <div className="min-w-0">
            <Link
              to={`/products/${item.listingId}`}
              onClick={onNavigate}
              className="line-clamp-2 text-sm font-medium text-ink hover:text-brand-700"
            >
              {item.title}
            </Link>
            <p className="mt-0.5 text-[0.8125rem] text-muted">
              {item.variantLabel && <span>{item.variantLabel} · </span>}
              <span className="font-mono text-xs">{item.sku}</span>
            </p>
          </div>
          <p className="text-sm font-semibold whitespace-nowrap text-ink tabular-nums">{formatMoney(lineTotal)}</p>
        </div>
        <div className="mt-auto flex items-center justify-between gap-3 pt-3">
          <div className="flex items-center gap-3">
            <QuantityStepper
              size="sm"
              value={item.quantity}
              max={Math.min(item.stock || 1, MAX_QTY)}
              onChange={(q) => setQuantity(item.variantId, q)}
              label={`Quantity for ${item.title}`}
            />
            {!compact && <Price amount={item.price} compareAt={item.compareAtPrice} size="sm" className="hidden sm:inline-flex" />}
          </div>
          <Button variant="danger-ghost" size="sm" iconOnly onClick={() => remove(item.variantId)} aria-label={`Remove ${item.title}`}>
            <Trash2 className="size-4" />
          </Button>
        </div>
        {error && <p className="mt-2 text-[0.8125rem] text-danger-600">{error}</p>}
      </div>
    </li>
  );
}
