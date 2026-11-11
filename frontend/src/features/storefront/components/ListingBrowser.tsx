import { useEffect, useRef, useState } from 'react';
import { PackageSearch, SlidersHorizontal, X } from 'lucide-react';
import { useCategories, useListings } from '@/api/storefront';
import { Alert, Badge, Button, Drawer, EmptyState, Pagination, Select } from '@/components/ui';
import { useSearchParamState } from '@/hooks/useSearchParamState';
import { formatNumber } from '@/lib/format';
import { ListingFilters, type FilterValues } from './ListingFilters';
import { ProductGrid } from './ProductCard';

const SORTS = [
  { value: '-created_at', label: 'Newest' },
  { value: 'price', label: 'Price: low to high' },
  { value: '-price', label: 'Price: high to low' },
  { value: 'created_at', label: 'Oldest' },
];

const KEYS = ['search', 'category', 'min_price', 'max_price', 'in_stock', 'sort', 'page'] as const;
const PER_PAGE = 12;

interface ListingBrowserProps {
  /** Restrict to one shop (shop detail page). */
  shop?: string;
  showShop?: boolean;
}

/** Filterable, sortable, paginated product grid driven by URL params. */
export function ListingBrowser({ shop, showShop = true }: ListingBrowserProps) {
  const { values, update, page, setPage } = useSearchParamState(KEYS);
  const [filtersOpen, setFiltersOpen] = useState(false);
  const topRef = useRef<HTMLDivElement>(null);
  const categories = useCategories();
  const categoryName = categories.data?.find((c) => c.slug === values.category)?.name ?? values.category.replace(/-/g, ' ');

  const query = useListings({
    shop,
    page,
    per_page: PER_PAGE,
    search: values.search,
    category: values.category,
    min_price: values.min_price,
    max_price: values.max_price,
    in_stock: values.in_stock === '1',
    sort: values.sort || '-created_at',
  });

  useEffect(() => {
    if (page > 1) topRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }, [page]);

  const filterValues: FilterValues = {
    category: values.category,
    min_price: values.min_price,
    max_price: values.max_price,
    in_stock: values.in_stock,
  };
  const onFilter = (patch: Partial<FilterValues>) => {
    update(patch);
    setFiltersOpen(false);
  };

  const chips = [
    values.search && { key: 'search', label: `“${values.search}”` },
    values.category && { key: 'category', label: categoryName },
    (values.min_price || values.max_price) && {
      key: 'price',
      label: `৳${values.min_price || '0'} – ${values.max_price ? `৳${values.max_price}` : 'any'}`,
    },
    values.in_stock === '1' && { key: 'in_stock', label: 'In stock' },
  ].filter(Boolean) as { key: string; label: string }[];

  const clearChip = (key: string) =>
    key === 'price' ? update({ min_price: '', max_price: '' }) : update({ [key]: '' } as Partial<Record<(typeof KEYS)[number], string>>);

  const total = query.data?.meta.total;

  return (
    <div ref={topRef} className="grid scroll-mt-24 gap-8 lg:grid-cols-[15rem_1fr] lg:gap-10">
      <aside className="hidden lg:block">
        <div className="sticky top-24 max-h-[calc(100dvh-7rem)] overflow-y-auto pr-1 pb-6">
          <ListingFilters values={filterValues} onChange={update} />
        </div>
      </aside>

      <div className="min-w-0">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <p className="text-sm text-muted" aria-live="polite">
            {total === undefined ? ' ' : `${formatNumber(total)} product${total === 1 ? '' : 's'}`}
          </p>
          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              className="lg:hidden"
              leadingIcon={<SlidersHorizontal className="size-4" />}
              onClick={() => setFiltersOpen(true)}
            >
              Filters
              {chips.length > 0 && <Badge tone="brand">{chips.length}</Badge>}
            </Button>
            <label htmlFor="sort" className="sr-only">
              Sort by
            </label>
            <Select id="sort" value={values.sort || '-created_at'} onChange={(e) => update({ sort: e.target.value })} className="h-8.5 w-44 text-[0.8125rem]">
              {SORTS.map((s) => (
                <option key={s.value} value={s.value}>
                  {s.label}
                </option>
              ))}
            </Select>
          </div>
        </div>

        {chips.length > 0 && (
          <div className="mt-4 flex flex-wrap items-center gap-2">
            {chips.map((c) => (
              <button
                key={c.key}
                type="button"
                onClick={() => clearChip(c.key)}
                className="inline-flex items-center gap-1 rounded-full border border-line bg-surface py-1 pr-2 pl-3 text-[0.8125rem] text-ink-soft capitalize transition-colors hover:border-line-strong hover:text-ink"
                aria-label={`Remove filter ${c.label}`}
              >
                {c.label}
                <X className="size-3.5" />
              </button>
            ))}
            <button
              type="button"
              className="text-[0.8125rem] font-medium text-brand-600 hover:text-brand-700"
              onClick={() => update({ search: '', category: '', min_price: '', max_price: '', in_stock: '' })}
            >
              Clear all
            </button>
          </div>
        )}

        <div className="mt-6">
          {query.isError ? (
            <Alert tone="danger" title="Couldn’t load products">
              {query.error.message}
            </Alert>
          ) : !query.isLoading && query.data?.items.length === 0 ? (
            <EmptyState
              icon={PackageSearch}
              title="No products match"
              description="Try a different search or remove a filter or two."
              action={
                chips.length > 0 ? (
                  <Button variant="outline" onClick={() => update({ search: '', category: '', min_price: '', max_price: '', in_stock: '' })}>
                    Clear filters
                  </Button>
                ) : undefined
              }
            />
          ) : (
            <div className={query.isPlaceholderData ? 'opacity-60 transition-opacity' : 'transition-opacity'}>
              <ProductGrid listings={query.data?.items} loading={query.isLoading} skeletons={PER_PAGE} showShop={showShop} className="md:grid-cols-3 xl:grid-cols-3" />
            </div>
          )}
        </div>

        {query.data && (
          <Pagination className="mt-12 border-t border-line pt-6" meta={query.data.meta} onPageChange={setPage} />
        )}
      </div>

      <Drawer open={filtersOpen} onClose={() => setFiltersOpen(false)} side="left" width="sm" title="Filters">
        <ListingFilters values={filterValues} onChange={onFilter} />
      </Drawer>
    </div>
  );
}
