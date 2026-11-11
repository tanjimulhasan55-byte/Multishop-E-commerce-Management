import { Check } from 'lucide-react';
import { cn } from '@/lib/cn';
import type { ListingVariant, ProductOption } from '@/types/models';
import { isValueAvailable, type Selection } from '../variants';

interface VariantPickerProps {
  options: ProductOption[];
  variants: ListingVariant[];
  selection: Selection;
  onSelect: (attributeId: number, valueId: number) => void;
}

/** Light swatches need a visible edge on white. */
function isLight(hex: string | null): boolean {
  if (!hex) return false;
  const m = hex.replace('#', '');
  if (m.length !== 6) return false;
  const [r, g, b] = [0, 2, 4].map((i) => Number.parseInt(m.slice(i, i + 2), 16));
  return 0.299 * r + 0.587 * g + 0.114 * b > 200;
}

export function VariantPicker({ options, variants, selection, onSelect }: VariantPickerProps) {
  return (
    <div className="space-y-5">
      {options.map((option) => {
        const selected = option.values.find((v) => v.id === selection[option.attribute_id]);
        return (
          <fieldset key={option.attribute_id}>
            <legend className="mb-2.5 text-[0.8125rem] font-medium text-ink-soft">
              {option.name}
              {selected && <span className="ml-1.5 font-normal text-muted">— {selected.value}</span>}
            </legend>
            <div className="flex flex-wrap gap-2" role="radiogroup" aria-label={option.name}>
              {option.values.map((value) => {
                const active = selection[option.attribute_id] === value.id;
                const available = isValueAvailable(option, value.id, variants, selection);
                const label = `${value.value}${available ? '' : ' (unavailable)'}`;

                if (option.type === 'color') {
                  return (
                    <button
                      key={value.id}
                      type="button"
                      role="radio"
                      aria-checked={active}
                      aria-label={label}
                      title={label}
                      disabled={!available}
                      onClick={() => onSelect(option.attribute_id, value.id)}
                      className={cn(
                        'relative flex size-10 items-center justify-center rounded-full transition-[box-shadow,transform] duration-150',
                        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500',
                        active ? 'ring-2 ring-brand-600 ring-offset-2' : 'ring-1 ring-line-strong hover:scale-105',
                        !available && 'cursor-not-allowed opacity-40 hover:scale-100',
                      )}
                    >
                      <span
                        className={cn('size-full rounded-full', isLight(value.color_hex) && 'border border-line-strong')}
                        style={{ backgroundColor: value.color_hex ?? '#e5e7eb' }}
                      />
                      {active && (
                        <Check
                          className={cn('absolute size-4', isLight(value.color_hex) ? 'text-ink' : 'text-white')}
                          strokeWidth={3}
                          aria-hidden
                        />
                      )}
                      {!available && (
                        <span className="absolute h-px w-[130%] rotate-45 bg-ink-soft" aria-hidden />
                      )}
                    </button>
                  );
                }

                return (
                  <button
                    key={value.id}
                    type="button"
                    role="radio"
                    aria-checked={active}
                    aria-label={label}
                    disabled={!available}
                    onClick={() => onSelect(option.attribute_id, value.id)}
                    className={cn(
                      'h-10 min-w-12 rounded-lg border px-3.5 text-sm font-medium transition-[border-color,background-color,color] duration-150',
                      'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500',
                      active
                        ? 'border-ink bg-ink text-white'
                        : 'border-line-strong bg-surface text-ink hover:border-ink',
                      !available && 'cursor-not-allowed border-dashed bg-subtle text-faint line-through hover:border-line-strong',
                    )}
                  >
                    {value.value}
                  </button>
                );
              })}
            </div>
          </fieldset>
        );
      })}
    </div>
  );
}
