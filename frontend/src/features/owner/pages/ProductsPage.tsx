import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import toast from 'react-hot-toast';
import { Boxes, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { errorMessage } from '@/api/errors';
import { useCatalog, useDeleteProduct } from '@/api/owner';
import {
  Alert,
  Badge,
  Button,
  Card,
  EmptyState,
  ImageBox,
  Input,
  PageHeader,
  Pagination,
  TableShell,
  TableSkeletonRows,
  Td,
  Th,
  THead,
  Tr,
  useConfirm,
  type BadgeTone,
} from '@/components/ui';
import { useDebounce } from '@/hooks/useDebounce';
import { formatMoney } from '@/lib/format';
import type { Product, ProductStatus } from '@/types/models';
import { ProductEditor } from '../components/ProductEditor';

const statusTone: Record<ProductStatus, BadgeTone> = { active: 'success', draft: 'warning', archived: 'neutral' };

export default function OwnerProductsPage() {
  const [params, setParams] = useSearchParams();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const debounced = useDebounce(search);
  const [editor, setEditor] = useState<{ open: boolean; product: Product | null }>({ open: false, product: null });
  const remove = useDeleteProduct();
  const confirm = useConfirm();

  useEffect(() => setPage(1), [debounced]);

  const query = useCatalog({ mine: true, search: debounced, page, per_page: 15 });

  // Deep links: ?new=1 or ?edit=<id> (from the catalog page).
  const editId = params.get('edit');
  const wantsNew = params.get('new') === '1';
  useEffect(() => {
    if (wantsNew) {
      setEditor({ open: true, product: null });
    } else if (editId && query.data) {
      const found = query.data.items.find((p) => p.id === editId);
      if (found) setEditor({ open: true, product: found });
    }
  }, [wantsNew, editId, query.data]);

  const close = () => {
    setEditor({ open: false, product: null });
    if (editId || wantsNew) setParams({}, { replace: true });
  };

  const onDelete = async (p: Product) => {
    const ok = await confirm({
      title: `Delete ${p.name}?`,
      description: 'The product will be removed from the shared catalog. Products listed in shops or ordered before cannot be deleted.',
      confirmLabel: 'Delete product',
    });
    if (!ok) return;
    remove.mutate(p.id, {
      onSuccess: () => toast.success('Product deleted'),
      onError: (e) => toast.error(errorMessage(e)),
    });
  };

  const items = query.data?.items;

  return (
    <div className="space-y-6">
      <PageHeader
        eyebrow="Catalog"
        title="My products"
        description="Products you created for the shared catalog. Define options like size and colour to generate variants."
        actions={
          <Button leadingIcon={<Plus className="size-4" />} onClick={() => setEditor({ open: true, product: null })}>
            New product
          </Button>
        }
      />

      <Card className="overflow-hidden">
        <div className="border-b border-line p-4">
          <label htmlFor="my-products-search" className="sr-only">
            Search my products
          </label>
          <Input
            id="my-products-search"
            type="search"
            placeholder="Search my products…"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            leading={<Search className="size-4" />}
            className="h-9 sm:max-w-xs"
          />
        </div>

        {query.isError ? (
          <div className="p-6">
            <Alert tone="danger">{query.error.message}</Alert>
          </div>
        ) : items && items.length === 0 ? (
          <EmptyState
            icon={Boxes}
            title={debounced ? 'No products match' : 'You haven’t created products yet'}
            description={debounced ? 'Try a different search.' : 'Create a product once and every shop can list it.'}
            action={
              !debounced ? (
                <Button leadingIcon={<Plus className="size-4" />} onClick={() => setEditor({ open: true, product: null })}>
                  Create your first product
                </Button>
              ) : undefined
            }
          />
        ) : (
          <TableShell>
            <THead>
              <tr>
                <Th>Product</Th>
                <Th>Status</Th>
                <Th>Base price</Th>
                <Th>Variants</Th>
                <Th>Shops</Th>
                <Th className="text-right">
                  <span className="sr-only">Actions</span>
                </Th>
              </tr>
            </THead>
            <tbody className={query.isPlaceholderData ? 'opacity-60' : undefined}>
              {!items ? (
                <TableSkeletonRows cols={6} />
              ) : (
                items.map((p) => (
                  <Tr key={p.id}>
                    <Td>
                      <div className="flex items-center gap-3">
                        <ImageBox image={p.thumbnail} alt="" variant="thumb" className="size-11 shrink-0 rounded-lg border border-line" />
                        <div className="min-w-0">
                          <button type="button" onClick={() => setEditor({ open: true, product: p })} className="block max-w-xs truncate text-left font-medium hover:text-brand-700">
                            {p.name}
                          </button>
                          <p className="truncate font-mono text-xs text-muted">
                            {p.sku}
                            {p.category_path && <span className="font-sans"> · {p.category_path}</span>}
                          </p>
                        </div>
                      </div>
                    </Td>
                    <Td>
                      <Badge tone={statusTone[p.status]} dot className="capitalize">
                        {p.status}
                      </Badge>
                    </Td>
                    <Td className="tabular-nums">{formatMoney(p.base_price)}</Td>
                    <Td className="tabular-nums">
                      {p.variants.length}
                      {p.options.length > 0 && <span className="ml-1.5 text-xs text-muted">{p.options.map((o) => o.name).join(' × ')}</span>}
                    </Td>
                    <Td className="tabular-nums">{p.shops_count}</Td>
                    <Td className="text-right whitespace-nowrap">
                      {p.can.update && (
                        <Button variant="ghost" size="sm" iconOnly onClick={() => setEditor({ open: true, product: p })} aria-label={`Edit ${p.name}`}>
                          <Pencil className="size-4" />
                        </Button>
                      )}
                      <Button
                        variant="danger-ghost"
                        size="sm"
                        iconOnly
                        onClick={() => onDelete(p)}
                        disabled={!p.can.delete}
                        aria-label={`Delete ${p.name}`}
                        title={p.can.delete ? 'Delete' : 'Listed in a shop or ordered — cannot be deleted'}
                      >
                        <Trash2 className="size-4" />
                      </Button>
                    </Td>
                  </Tr>
                ))
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

      <ProductEditor open={editor.open} product={editor.product} onClose={close} />
    </div>
  );
}
