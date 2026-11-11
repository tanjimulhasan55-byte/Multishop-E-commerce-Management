import { cn } from '@/lib/cn';
import { useLocaleStore } from '@/stores/locale';
import type { Locale } from '@/types/models';

const options: { value: Locale; label: string; full: string }[] = [
  { value: 'en', label: 'EN', full: 'English' },
  { value: 'bn', label: 'বাং', full: 'বাংলা' },
];

/** EN / বাংলা segmented toggle. Changing it refetches translated content. */
export function LocaleSwitcher({ className }: { className?: string }) {
  const { locale, setLocale } = useLocaleStore();
  return (
    <div role="radiogroup" aria-label="Language" className={cn('inline-flex rounded-lg bg-subtle p-0.5', className)}>
      {options.map((o) => (
        <button
          key={o.value}
          type="button"
          role="radio"
          aria-checked={locale === o.value}
          aria-label={o.full}
          lang={o.value}
          onClick={() => setLocale(o.value)}
          className={cn(
            'h-7 min-w-9 rounded-md px-2 text-xs font-semibold transition-all duration-150',
            locale === o.value ? 'bg-surface text-ink shadow-xs' : 'text-muted hover:text-ink',
          )}
        >
          {o.label}
        </button>
      ))}
    </div>
  );
}
