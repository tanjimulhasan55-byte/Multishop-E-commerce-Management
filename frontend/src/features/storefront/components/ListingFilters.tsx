import { useEffect, useMemo, useState, type FormEvent } from 'react';
import { useCategories } from '@/api/storefront';
import { Button, Checkbox, Input, Skeleton } from '@/components/ui';
import { ancestorIds, buildCategoryTree } from '@/lib/categories';
import { CategoryTree } from './CategoryTree';

export interface FilterValues {
  category: string;
  min_price: string;
  max_price: string;
  in_stock: string;
}

interface ListingFiltersProps {
  values: FilterValues;
  onChange: (patch: Partial<FilterValues>) => void;
  showCategories?: boolean;
}

/** Sidebar filters: category tree, price range and availability. */
export function ListingFilters({ values, onChange, showCategories = true }: ListingFiltersProps) {
  const categories = useCategories();
  const tree = useMemo(() => buildCategoryTree(categories.data ?? []), [categories.data]);
  const expanded = useMemo(() => ancestorIds(categories.data ?? [], values.category), [categories.data, values.category]);

  const [min, setMin] = useState(values.min_price);
  const [max, setMax] = useState(values.max_price);
  useEffect(() => {
    setMin(values.min_price);
    setMax(values.max_price);
  }, [values.min_price, values.max_price]);

  const applyPrice = (e: FormEvent) => {
    e.preventDefault();
    onChange({ min_price: min, max_price: max });
  };

  const hasPrice = !!(values.min_price || values.max_price);

  return (
    <div className="space-y-8">
      {showCategories && (
        <section aria-labelledby="filter-categories">
          <h2 id="filter-categories" className="mb-3 text-xs font-semibold tracking-wider text-muted uppercase">
            Categories
          </h2>
          {categories.isLoading ? (
            <div className="space-y-2">
              {Array.from({ length: 6 }, (_, i) => (
                <Skeleton key={i} className="h-7" />
              ))}
            </div>
          ) : (
            <CategoryTree nodes={tree} selected={values.category} onSelect={(category) => onChange({ category })} expandedIds={expanded} />
          )}
        </section>
      )}

      <section aria-labelledby="filter-price">
        <div className="mb-3 flex items-center justify-between">
          <h2 id="filter-price" className="text-xs font-semibold tracking-wider text-muted uppercase">
            Price (৳)
          </h2>
          {hasPrice && (
            <button type="button" className="text-xs font-medium text-brand-600 hover:text-brand-700" onClick={() => onChange({ min_price: '', max_price: '' })}>
              Reset
            </button>
          )}
        </div>
        <form onSubmit={applyPrice} className="space-y-2.5">
          <div className="flex items-center gap-2">
            <Input type="number" min={0} inputMode="decimal" placeholder="Min" aria-label="Minimum price" value={min} onChange={(e) => setMin(e.target.value)} />
            <span className="text-faint" aria-hidden>
              –
            </span>
            <Input type="number" min={0} inputMode="decimal" placeholder="Max" aria-label="Maximum price" value={max} onChange={(e) => setMax(e.target.value)} />
          </div>
          <Button type="submit" variant="outline" size="sm" block>
            Apply price
          </Button>
        </form>
      </section>

      <section aria-labelledby="filter-availability">
        <h2 id="filter-availability" className="mb-3 text-xs font-semibold tracking-wider text-muted uppercase">
          Availability
        </h2>
        <Checkbox
          label="In stock only"
          checked={values.in_stock === '1'}
          onChange={(e) => onChange({ in_stock: e.target.checked ? '1' : '' })}
        />
      </section>
    </div>
  );
}
