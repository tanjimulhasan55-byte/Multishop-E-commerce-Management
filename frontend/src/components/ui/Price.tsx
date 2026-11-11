import { cn } from '@/lib/cn';
import { discountPercent, formatMoney } from '@/lib/format';
import type { Money } from '@/types/models';

interface PriceProps {
  amount: Money | number;
  compareAt?: Money | null;
  from?: boolean;
  size?: 'sm' | 'md' | 'lg' | 'xl';
  className?: string;
}

const sizes = {
  sm: 'text-sm',
  md: 'text-[0.9375rem]',
  lg: 'text-lg',
  xl: 'text-3xl tracking-tight',
};

export function Price({ amount, compareAt, from, size = 'md', className }: PriceProps) {
  const pct = typeof amount === 'string' ? discountPercent(amount, compareAt ?? null) : null;
  return (
    <span className={cn('inline-flex flex-wrap items-baseline gap-x-2 gap-y-0.5', className)}>
      {from && <span className="text-xs font-normal text-muted">from</span>}
      <span className={cn('font-semibold text-ink tabular-nums', sizes[size])}>{formatMoney(amount)}</span>
      {pct !== null && compareAt && (
        <>
          <span className={cn('text-muted line-through tabular-nums', size === 'xl' ? 'text-lg' : 'text-[0.8125rem]')}>
            <span className="sr-only">Was </span>
            {formatMoney(compareAt)}
          </span>
          {size === 'xl' && <span className="text-sm font-semibold text-danger-600">Save {pct}%</span>}
        </>
      )}
    </span>
  );
}
