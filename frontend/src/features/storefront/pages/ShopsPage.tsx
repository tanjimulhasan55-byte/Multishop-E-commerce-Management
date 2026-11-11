import { useEffect, useState } from 'react';
import { Search, Store } from 'lucide-react';
import { useShops } from '@/api/storefront';
import { Alert, EmptyState, Input, PageHeader, Pagination, Select } from '@/components/ui';
import { useDebounce } from '@/hooks/useDebounce';
import { useSearchParamState } from '@/hooks/useSearchParamState';
import { ShopCard, ShopCardSkeleton } from '../components/ShopCard';

const KEYS = ['search', 'sort', 'page'] as const;

export default function ShopsPage() {
  const { values, update, page, setPage } = useSearchParamState(KEYS);
  const [search, setSearch] = useState(values.search);
  const debounced = useDebounce(search);

  useEffect(() => {
    if (debounced !== values.search) update({ search: debounced });
  }, [debounced, values.search, update]);

  const query = useShops({ page, per_page: 12, search: values.search, sort: values.sort || 'name_en' });

  return (
    <div className="container-page py-8 sm:py-12">
      <PageHeader
        eyebrow="Directory"
        title="Shops"
        description="Independent sellers from Dhaka to Sylhet. Every shop ships and invoices its own orders."
      />

      <div className="mt-8 flex flex-col gap-3 sm:flex-row">
        <div className="flex-1 sm:max-w-sm">
          <label htmlFor="shop-search" className="sr-only">
            Search shops
          </label>
          <Input
            id="shop-search"
            type="search"
            placeholder="Search shops…"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            leading={<Search className="size-4" />}
          />
        </div>
        <label htmlFor="shop-sort" className="sr-only">
          Sort shops
        </label>
        <Select id="shop-sort" value={values.sort || 'name_en'} onChange={(e) => update({ sort: e.target.value })} className="sm:w-48">
          <option value="name_en">Name (A–Z)</option>
          <option value="-name_en">Name (Z–A)</option>
          <option value="-created_at">Newest</option>
        </Select>
      </div>

      <div className="mt-8">
        {query.isError ? (
          <Alert tone="danger" title="Couldn’t load shops">
            {query.error.message}
          </Alert>
        ) : !query.isLoading && query.data?.items.length === 0 ? (
          <EmptyState icon={Store} title="No shops found" description="Try a different search term." />
        ) : (
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {query.isLoading
              ? Array.from({ length: 6 }, (_, i) => <ShopCardSkeleton key={i} />)
              : query.data?.items.map((s) => <ShopCard key={s.id} shop={s} />)}
          </div>
        )}
      </div>

      {query.data && <Pagination className="mt-10 border-t border-line pt-6" meta={query.data.meta} onPageChange={setPage} />}
    </div>
  );
}
