import { Minus, Plus } from 'lucide-react';
import { cn } from '@/lib/cn';

interface QuantityStepperProps {
  value: number;
  onChange: (value: number) => void;
  min?: number;
  max?: number;
  size?: 'sm' | 'md';
  label?: string;
  disabled?: boolean;
}

export function QuantityStepper({ value, onChange, min = 1, max = 100, size = 'md', label = 'Quantity', disabled }: QuantityStepperProps) {
  const btn = cn(
    'flex items-center justify-center text-ink-soft transition-colors duration-150 hover:bg-subtle hover:text-ink disabled:opacity-35 disabled:hover:bg-transparent',
    size === 'sm' ? 'size-8' : 'size-10',
  );
  return (
    <div
      className={cn('inline-flex items-center overflow-hidden rounded-lg border border-line-strong bg-surface shadow-xs', disabled && 'opacity-60')}
      role="group"
      aria-label={label}
    >
      <button type="button" className={btn} onClick={() => onChange(value - 1)} disabled={disabled || value <= min} aria-label="Decrease quantity">
        <Minus className="size-3.5" />
      </button>
      <input
        type="number"
        inputMode="numeric"
        min={min}
        max={max}
        value={value}
        disabled={disabled}
        aria-label={label}
        onChange={(e) => {
          const n = Number.parseInt(e.target.value, 10);
          if (Number.isFinite(n)) onChange(Math.min(max, Math.max(min, n)));
        }}
        className={cn(
          'w-11 border-x border-line bg-transparent text-center text-sm font-medium tabular-nums focus:outline-none',
          '[appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none',
          size === 'sm' ? 'h-8' : 'h-10',
        )}
      />
      <button type="button" className={btn} onClick={() => onChange(value + 1)} disabled={disabled || value >= max} aria-label="Increase quantity">
        <Plus className="size-3.5" />
      </button>
    </div>
  );
}
