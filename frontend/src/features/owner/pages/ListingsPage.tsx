import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import toast from 'react-hot-toast';
import { ExternalLink, ListChecks, MoreHorizontal, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { asTranslations } from '@/api/jsonapi';
import { errorMessage } from '@/api/errors';
import { useDeleteListing, useShopListings, useToggleListing } from '@/api/owner';
import {
  Alert,
  Badge,
  ButtonLink,
  Card,
  EmptyState,
  ImageBox,
  Input,
  Menu,
  MenuButton,
  MenuLink,
  MenuSeparator,
  PageHeader,
  Pagination,
  Switch,
  TableShell,
  TableSkeletonRows,
  Td,
  Th,
  THead,
  Tr,
  Tabs,
  useConfirm,
  buttonClasses,
} from '@/components/ui';
import { useDebounce } from '@/hooks/useDebounce';
import { useSearchParamState } from '@/hooks/useSearchParamState';
import { formatMoney, formatNumber } from '@/lib/format';
import type { Listing } from '@/types/models';
import { ListingEditor } from '../components/ListingEditor';
import { useCurrentShop } from '../useCurrentShop';

const KEYS = ['search', 'status', 'low_stock', 'page'] as const;
type StatusTab = 'all' | 'active' | 'inactive' | 'low';
const LOW_STOCK = 5;

export default function ListingsPage() {
  const { shop, isLoading: shopLoading } = useCurrentShop();
  const { values, update, page, setPage } = useSearchParamState(KEYS);
  const [search, setSearch] = useState(values.search);
  const debounced = useDebounce(search);
  const [editing, setEditing] = useState<Listing | null>(null);
  const toggle = useToggleListing();
  const remove = useDeleteListing();
  const confirm = useConfirm();

  useEffect(() => {
    if (debounced !== values.search) update({ search: debounced });
  }, [debounced, values.search, update]);

  const tab: StatusTab = values.low_stock === '1' ? 'low' : values.status === '1' ? 'active' : values.status === '0' ? 'inactive' : 'all';

  const query = useShopListings(shop?.slug ?? null, {
    page,
    search: values.search,
    is_active: values.status === '1' || values.status === '0' ? values.status : '',
    low_stock: values.low_stock === '1',
  });

  const setTab = (t: StatusTab) =>
    update({ status: t === 'active' ? '1' : t === 'inactive' ? '0' : '', low_stock: t === 'low' ? '1' : '' });

  const onToggle = (listing: Listing, next: boolean) =>
    toggle.mutate(
      {
        id: listing.id,
        payload: {
          is_active: next,
          title: asTranslations(listing.overrides?.title),
          description: asTranslations(listing.overrides?.description),
        },
      },
      {
        onSuccess: () => toast.success(next ? 'Listing is live' : 'Listing hidden'),
        onError: (e) => toast.error(errorMessage(e)),
      },
    );

  const onDelete = async (listing: Listing) => {
    const ok = await confirm({
      title: 'Remove listing?',
      description: (
        <>
          <strong className="text-ink">{listing.title}</strong> will be removed from {shop?.name}. The product stays in the shared catalog
          and past orders are unaffected.
        </>
      ),
      confirmLabel: 'Remove listing',
    });
    if (!ok) return;
    remove.mutate(listing.id, {
      onSuccess: () => toast.success('Listing removed'),
      onError: (e) => toast.error(errorMessage(e)),
    });
  };

  if (!shopLoading && !shop) {
    return (
      <Card>
        <EmptyState icon={ListChecks} title="No shop selected" description="You need a shop before you can list products." />
      </Card>
    );
  }

  const items = query.data?.items;

  return (
    <div className="space-y-6">
      <PageHeader
        eyebrow={shop?.name ?? 'Listings'}
        title="Listings"
        description="Products your shop sells, with shop-specific prices, stock and content."
        actions={
          <ButtonLink to="/owner/catalog?not_listed=1" leadingIcon={<Plus className="size-4" />}>
            Add from catalog
          </ButtonLink>
        }
      />

      <Card className="overflow-hidden">
        <div className="flex flex-col gap-3 border-b border-line px-5 pt-4 sm:px-6 md:flex-row md:items-end md:justify-between">
          <Tabs
            label="Filter listings"
            value={tab}
            onChange={setTab}
            items={[
              { value: 'all', label: 'All' },
              { value: 'active', label: 'Active' },
              { value: 'inactive', label: 'Inactive' },
              { value: 'low', label: 'Low stock' },
            ]}
            className="border-b-0"
          />
          <div className="pb-3 md:w-72">
            <label htmlFor="listing-search" className="sr-only">
              Search listings
            </label>
            <Input
              id="listing-search"
              type="search"
              placeholder="Search by title or SKU…"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              leading={<Search className="size-4" />}
              className="h-9"
            />
          </div>
        </div>

        {query.isError ? (
          <div className="p-6">
            <Alert tone="danger">{query.error.message}</Alert>
          </div>
        ) : items && items.length === 0 ? (
          <EmptyState
            icon={ListChecks}
            title={values.search || tab !== 'all' ? 'No listings match' : 'No listings yet'}
            description={values.search || tab !== 'all' ? 'Try another search or filter.' : 'Add products from the shared catalog to start selling.'}
            action={tab === 'all' && !values.search ? <ButtonLink to="/owner/catalog">Browse catalog</ButtonLink> : undefined}
          />
        ) : (
          <TableShell>
            <THead>
              <tr>
                <Th>Product</Th>
                <Th>Price</Th>
                <Th>Stock</Th>
                <Th>Variants</Th>
                <Th>Active</Th>
                <Th className="text-right">
                  <span className="sr-only">Actions</span>
                </Th>
              </tr>
            </THead>
            <tbody className={query.isPlaceholderData ? 'opacity-60' : undefined}>
              {!items ? (
                <TableSkeletonRows cols={6} />
              ) : (
                items.map((l) => {
                  const totalStock = l.variants.reduce((n, v) => n + v.stock, 0);
                  const activeVariants = l.variants.filter((v) => v.is_active).length;
                  return (
                    <Tr key={l.id}>
                      <Td>
                        <div className="flex items-center gap-3">
                          <ImageBox image={l.images} alt="" variant="thumb" className="size-11 shrink-0 rounded-lg border border-line" />
                          <div className="min-w-0">
                            <button
                              type="button"
                              onClick={() => setEditing(l)}
                              className="block max-w-xs truncate text-left font-medium text-ink hover:text-brand-700"
                            >
                              {l.title}
                            </button>
                            <p className="truncate font-mono text-xs text-muted">{l.sku}</p>
                          </div>
                        </div>
                      </Td>
                      <Td className="whitespace-nowrap tabular-nums">
                        {l.has_options && <span className="mr-1 text-xs text-muted">from</span>}
                        {formatMoney(l.price)}
                      </Td>
                      <Td>
                        {totalStock === 0 ? (
                          <Badge tone="danger" dot>
                            Out of stock
                          </Badge>
                        ) : totalStock <= LOW_STOCK ? (
                          <Badge tone="warning" dot>
                            {totalStock} left
                          </Badge>
                        ) : (
                          <span className="tabular-nums">{formatNumber(totalStock)}</span>
                        )}
                      </Td>
                      <Td className="text-muted tabular-nums">
                        {activeVariants}/{l.variants.length}
                      </Td>
                      <Td>
                        <Switch size="sm" checked={l.is_active} onChange={(v) => onToggle(l, v)} label={`${l.title} active`} />
                      </Td>
                      <Td className="text-right">
                        <Menu
                          trigger={(props) => (
                            <button type="button" {...props} aria-label={`Actions for ${l.title}`} className={buttonClasses({ variant: 'ghost', size: 'sm', iconOnly: true })}>
                              <MoreHorizontal className="size-4" />
                            </button>
                          )}
                        >
                          <MenuButton icon={<Pencil className="size-4" />} onClick={() => setEditing(l)}>
                            Edit listing
                          </MenuButton>
                          <MenuLink to={`/products/${l.id}`} icon={<ExternalLink className="size-4" />}>
                            View in store
                          </MenuLink>
                          <MenuSeparator />
                          <MenuButton danger icon={<Trash2 className="size-4" />} onClick={() => onDelete(l)}>
                            Remove
                          </MenuButton>
                        </Menu>
                      </Td>
                    </Tr>
                  );
                })
              )}
            </tbody>
          </TableShell>
        )}

        {query.data && query.data.meta.total > 0 && (
          <div className="border-t border-line px-5 py-3.5 sm:px-6">
            <Pagination meta={query.data.meta} onPageChange={setPage} />
          </div>
        )}
      </Card>

      <p className="text-[0.8125rem] text-muted">
        Tip: inactive listings stay editable but are hidden from <Link to="/products" className="text-brand-600 hover:text-brand-700">the storefront</Link>.
      </p>

      <ListingEditor listing={editing} onClose={() => setEditing(null)} />
    </div>
  );
}
