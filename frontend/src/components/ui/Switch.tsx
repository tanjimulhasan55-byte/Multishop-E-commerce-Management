import { cn } from '@/lib/cn';

interface SwitchProps {
  checked: boolean;
  onChange: (checked: boolean) => void;
  label: string;
  /** Show the label next to the switch (otherwise screen-reader only). */
  showLabel?: boolean;
  disabled?: boolean;
  size?: 'sm' | 'md';
}

export function Switch({ checked, onChange, label, showLabel, disabled, size = 'md' }: SwitchProps) {
  return (
    <label className={cn('inline-flex items-center gap-2.5', disabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer')}>
      <button
        type="button"
        role="switch"
        aria-checked={checked}
        aria-label={showLabel ? undefined : label}
        disabled={disabled}
        onClick={() => onChange(!checked)}
        className={cn(
          'relative inline-flex shrink-0 items-center rounded-full transition-colors duration-200',
          'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500',
          size === 'sm' ? 'h-5 w-9' : 'h-6 w-11',
          checked ? 'bg-brand-600' : 'bg-line-strong',
        )}
      >
        <span
          aria-hidden
          className={cn(
            'inline-block rounded-full bg-white shadow-sm transition-transform duration-200 ease-out',
            size === 'sm' ? 'size-4' : 'size-5',
            checked ? (size === 'sm' ? 'translate-x-[18px]' : 'translate-x-[22px]') : 'translate-x-0.5',
          )}
        />
      </button>
      {showLabel && <span className="text-sm text-ink-soft">{label}</span>}
    </label>
  );
}
