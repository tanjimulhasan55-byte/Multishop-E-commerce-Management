import { useEffect, useState } from 'react';
import { Check, Library, Pencil, Plus, Search, Store } from 'lucide-react';
import { useCatalog } from '@/api/owner';
import {
  Alert,
  Badge,
  Button,
  ButtonLink,
  Card,
  Checkbox,
  EmptyState,
  ImageBox,
  Input,
  PageHeader,
  Pagination,
  Skeleton,
} from '@/components/ui';
import { useDebounce } from '@/hooks/useDebounce';
import { useSearchParamState } from '@/hooks/useSearchParamState';
import { formatMoney } from '@/lib/format';
import type { Product } from '@/types/models';
import { AddToShopModal } from '../components/AddToShopModal';
import { CategorySelect } from '../components/CategorySelect';
import { useCurrentShop } from '../useCurrentShop';

const KEYS = ['search', 'category', 'mine', 'not_listed', 'page'] as const;

function CatalogCard({ product, onAdd }: { product: Product; onAdd: () => void }) {
  const listed = !!product.listed_in_shop;
  const unavailable = product.status !== 'active';
  return (
    <article className="flex flex-col overflow-hidden rounded-2xl border border-line bg-surface shadow-xs transition-shadow duration-200 hover:shadow-raised">
      <div className="relative">
        <ImageBox image={product.thumbnail} alt={product.name} aspect="aspect-[4/3]" />
        <div className="absolute top-3 left-3 flex flex-wrap gap-1.5">
          {product.is_mine && <Badge tone="brand" className="bg-brand-50/95 backdrop-blur">Yours</Badge>}
          {unavailable && <Badge className="bg-surface/95 capitalize backdrop-blur">{product.status}</Badge>}
        </div>
      </div>
      <div className="flex flex-1 flex-col p-4">
        <h3 className="line-clamp-2 text-sm font-medium">{product.name}</h3>
        <p className="mt-0.5 font-mono text-xs text-muted">{product.sku}</p>
        <div className="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[0.8125rem] text-muted">
          <span className="font-semibold text-ink tabular-nums">{formatMoney(product.base_price)}</span>
          <span>
            {product.variants.length} variant{product.variants.length === 1 ? '' : 's'}
          </span>
          <span className="inline-flex items-center gap-1">
            <Store className="size-3.5" aria-hidden /> {product.shops_count}
          </span>
        </div>
        {product.category_path && <p className="mt-1 truncate text-xs text-faint">{product.category_path}</p>}
        <div className="mt-auto flex gap-2 pt-4">
          {listed ? (
            <span className="inline-flex h-8.5 flex-1 items-center justify-center gap-1.5 rounded-lg bg-success-50 text-[0.8125rem] font-medium text-success-700">
              <Check className="size-4" aria-hidden /> In your shop
            </span>
          ) : (
            <Button size="sm" className="flex-1" leadingIcon={<Plus className="size-4" />} onClick={onAdd} disabled={unavailable} title={unavailable ? 'Only active products can be listed' : undefined}>
              Add to shop
            </Button>
          )}
          {product.can.update && (
            <ButtonLink to={`/owner/products?edit=${product.id}`} size="sm" variant="outline" iconOnly aria-label={`Edit ${product.name}`}>
              <Pencil className="size-4" />
            </ButtonLink>
          )}
        </div>
      </div>
    </article>
  );
}

export default function CatalogPage() {
  const { shop } = useCurrentShop();
  const { values, update, page, setPage } = useSearchParamState(KEYS);
  const [search, setSearch] = useState(values.search);
  const debounced = useDebounce(search);
  const [adding, setAdding] = useState<Product | null>(null);

  useEffect(() => {
    if (debounced !== values.search) update({ search: debounced });
  }, [debounced, values.search, update]);

  const query = useCatalog({
    shop: shop?.slug,
    page,
    per_page: 12,
    search: values.search,
    category: values.category,
    mine: values.mine === '1',
    not_listed: values.not_listed === '1',
  });

  return (
    <div className="space-y-6">
      <PageHeader
        eyebrow="Catalog"
        title="Shared catalog"
        description="Products available to every shop. Add any of them to your shop with your own price and stock."
        actions={
          <ButtonLink to="/owner/products?new=1" variant="outline" leadingIcon={<Plus className="size-4" />}>
            New product
          </ButtonLink>
        }
      />

      <Card className="flex flex-col gap-3 p-4 lg:flex-row lg:items-center">
        <div className="flex-1">
          <label htmlFor="catalog-search" className="sr-only">
            Search catalog
          </label>
          <Input
            id="catalog-search"
            type="search"
            placeholder="Search name or SKU…"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            leading={<Search className="size-4" />}
          />
        </div>
        <div className="lg:w-56">
          <label htmlFor="catalog-category" className="sr-only">
            Category
          </label>
          <CategorySelect id="catalog-category" emptyLabel="All categories" value={values.category} onChange={(e) => update({ category: e.target.value })} />
        </div>
        <div className="flex flex-wrap gap-x-5 gap-y-2 lg:pl-2">
          <Checkbox label="Only mine" checked={values.mine === '1'} onChange={(e) => update({ mine: e.target.checked ? '1' : '' })} />
          <Checkbox
            label={`Not in ${shop?.name ?? 'my shop'}`}
            checked={values.not_listed === '1'}
            onChange={(e) => update({ not_listed: e.target.checked ? '1' : '' })}
            disabled={!shop}
          />
        </div>
      </Card>

      {query.isError ? (
        <Alert tone="danger">{query.error.message}</Alert>
      ) : query.data?.items.length === 0 ? (
        <Card>
          <EmptyState
            icon={Library}
            title="Nothing here"
            description={values.not_listed === '1' ? 'Your shop already lists every matching product.' : 'No catalog products match these filters.'}
            action={<ButtonLink to="/owner/products?new=1">Create a product</ButtonLink>}
          />
        </Card>
      ) : (
        <div className={`grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 ${query.isPlaceholderData ? 'opacity-60' : ''}`}>
          {query.isLoading
            ? Array.from({ length: 8 }, (_, i) => <Skeleton key={i} className="h-80 rounded-2xl" />)
            : query.data?.items.map((p) => <CatalogCard key={p.id} product={p} onAdd={() => setAdding(p)} />)}
        </div>
      )}

      {query.data && <Pagination meta={query.data.meta} onPageChange={setPage} className="border-t border-line pt-5" />}

      <AddToShopModal product={adding} shop={shop} onClose={() => setAdding(null)} />
    </div>
  );
}
