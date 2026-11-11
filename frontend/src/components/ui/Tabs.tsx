import { useRef, type KeyboardEvent, type ReactNode } from 'react';
import { cn } from '@/lib/cn';

export interface TabItem<V extends string> {
  value: V;
  label: ReactNode;
  count?: number;
}

interface TabsProps<V extends string> {
  items: TabItem<V>[];
  value: V;
  onChange: (value: V) => void;
  label: string;
  className?: string;
  variant?: 'underline' | 'pill';
}

/** Accessible tablist with roving arrow-key navigation. */
export function Tabs<V extends string>({ items, value, onChange, label, className, variant = 'underline' }: TabsProps<V>) {
  const refs = useRef<(HTMLButtonElement | null)[]>([]);

  const onKeyDown = (e: KeyboardEvent, index: number) => {
    const delta = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0;
    if (!delta) return;
    e.preventDefault();
    const next = (index + delta + items.length) % items.length;
    refs.current[next]?.focus();
    onChange(items[next].value);
  };

  return (
    <div
      role="tablist"
      aria-label={label}
      className={cn(
        'scrollbar-none flex overflow-x-auto',
        variant === 'underline' ? 'gap-6 border-b border-line' : 'gap-1 rounded-xl bg-subtle p-1',
        className,
      )}
    >
      {items.map((item, i) => {
        const selected = item.value === value;
        return (
          <button
            key={item.value}
            ref={(el) => {
              refs.current[i] = el;
            }}
            role="tab"
            type="button"
            aria-selected={selected}
            tabIndex={selected ? 0 : -1}
            onClick={() => onChange(item.value)}
            onKeyDown={(e) => onKeyDown(e, i)}
            className={cn(
              'inline-flex shrink-0 items-center gap-2 text-sm font-medium whitespace-nowrap transition-colors duration-150',
              variant === 'underline'
                ? cn('-mb-px border-b-2 pt-1 pb-3', selected ? 'border-brand-600 text-ink' : 'border-transparent text-muted hover:text-ink')
                : cn('rounded-lg px-3 py-1.5', selected ? 'bg-surface text-ink shadow-xs' : 'text-muted hover:text-ink'),
            )}
          >
            {item.label}
            {item.count !== undefined && (
              <span
                className={cn(
                  'rounded-full px-1.5 py-px text-[0.6875rem] tabular-nums',
                  selected ? 'bg-brand-50 text-brand-700' : 'bg-subtle text-muted',
                )}
              >
                {item.count}
              </span>
            )}
          </button>
        );
      })}
    </div>
  );
}
