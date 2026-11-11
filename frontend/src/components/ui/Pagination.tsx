import { ChevronLeft, ChevronRight } from 'lucide-react';
import { cn } from '@/lib/cn';
import { formatNumber } from '@/lib/format';
import type { PaginationMeta } from '@/types/api';

interface PaginationProps {
  meta: PaginationMeta;
  onPageChange: (page: number) => void;
  className?: string;
  /** Show "Showing x–y of z". */
  summary?: boolean;
}

/** Page numbers with ellipses: 1 … 4 5 [6] 7 8 … 20 */
function pageWindow(current: number, last: number): (number | 'gap')[] {
  const pages = new Set([1, last, current - 1, current, current + 1]);
  const sorted = [...pages].filter((p) => p >= 1 && p <= last).sort((a, b) => a - b);
  const out: (number | 'gap')[] = [];
  sorted.forEach((p, i) => {
    if (i > 0 && p - sorted[i - 1] > 1) out.push('gap');
    out.push(p);
  });
  return out;
}

export function Pagination({ meta, onPageChange, className, summary = true }: PaginationProps) {
  const { current_page: current, last_page: last, from, to, total } = meta;
  if (total === 0) return null;

  const itemClass =
    'inline-flex h-9 min-w-9 items-center justify-center rounded-lg px-2 text-sm font-medium transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-brand-500';

  return (
    <nav className={cn('flex flex-col items-center justify-between gap-3 sm:flex-row', className)} aria-label="Pagination">
      {summary ? (
        <p className="text-[0.8125rem] text-muted">
          Showing <span className="font-medium text-ink">{from ?? 0}</span>–<span className="font-medium text-ink">{to ?? 0}</span> of{' '}
          <span className="font-medium text-ink">{formatNumber(total)}</span>
        </p>
      ) : (
        <span />
      )}
      {last > 1 && (
        <div className="flex items-center gap-1">
          <button
            type="button"
            className={cn(itemClass, 'text-ink-soft hover:bg-subtle disabled:opacity-40 disabled:hover:bg-transparent')}
            onClick={() => onPageChange(current - 1)}
            disabled={current <= 1}
            aria-label="Previous page"
          >
            <ChevronLeft className="size-4" />
          </button>
          {pageWindow(current, last).map((p, i) =>
            p === 'gap' ? (
              <span key={`gap-${i}`} className="px-1 text-faint" aria-hidden>
                …
              </span>
            ) : (
              <button
                key={p}
                type="button"
                onClick={() => onPageChange(p)}
                aria-current={p === current ? 'page' : undefined}
                className={cn(
                  itemClass,
                  p === current ? 'bg-ink text-white shadow-xs' : 'text-ink-soft hover:bg-subtle',
                  // Keep it compact on phones.
                  p !== current && Math.abs(p - current) > 1 && 'hidden sm:inline-flex',
                )}
              >
                {p}
              </button>
            ),
          )}
          <button
            type="button"
            className={cn(itemClass, 'text-ink-soft hover:bg-subtle disabled:opacity-40 disabled:hover:bg-transparent')}
            onClick={() => onPageChange(current + 1)}
            disabled={current >= last}
            aria-label="Next page"
          >
            <ChevronRight className="size-4" />
          </button>
        </div>
      )}
    </nav>
  );
}
