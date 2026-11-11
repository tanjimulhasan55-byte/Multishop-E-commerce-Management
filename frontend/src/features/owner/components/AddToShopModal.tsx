import { useEffect, useState, type FormEvent, type ReactNode } from 'react';
import toast from 'react-hot-toast';
import { ChevronDown } from 'lucide-react';
import { useCreateListing } from '@/api/owner';
import { Button, Field, ImageBox, ImagePicker, Input, Modal, Switch, type ImagePickerValue } from '@/components/ui';
import { useFormErrors } from '@/hooks/useFormErrors';
import { cn } from '@/lib/cn';
import { formatMoney } from '@/lib/format';
import type { Product, Shop, Translations } from '@/types/models';
import { TranslatableFields } from './TranslatableFields';
import { toVariantPayload, validateVariantRows, VariantPricingTable, type VariantRow } from './VariantPricingTable';

interface AddToShopModalProps {
  product: Product | null;
  shop: Shop | null;
  onClose: () => void;
}

function Disclosure({ title, description, open, onToggle, children }: {
  title: string;
  description: string;
  open: boolean;
  onToggle: () => void;
  children: ReactNode;
}) {
  return (
    <section className="rounded-xl border border-line">
      <button type="button" onClick={onToggle} aria-expanded={open} className="flex w-full items-center justify-between gap-3 px-4 py-3 text-left">
        <span>
          <span className="block text-sm font-medium">{title}</span>
          <span className="block text-[0.8125rem] text-muted">{description}</span>
        </span>
        <ChevronDown className={cn('size-4 shrink-0 text-muted transition-transform duration-200', open && 'rotate-180')} aria-hidden />
      </button>
      {open && <div className="border-t border-line p-4">{children}</div>}
    </section>
  );
}

export function AddToShopModal({ product, shop, onClose }: AddToShopModalProps) {
  const create = useCreateListing();
  const { errors, errorFor, handleError, clearError, setErrors, reset } = useFormErrors();
  const [price, setPrice] = useState('');
  const [compareAt, setCompareAt] = useState('');
  const [stock, setStock] = useState('10');
  const [active, setActive] = useState(true);
  const [perVariant, setPerVariant] = useState(false);
  const [rows, setRows] = useState<VariantRow[]>([]);
  const [customContent, setCustomContent] = useState(false);
  const [title, setTitle] = useState<Translations>({});
  const [description, setDescription] = useState<Translations>({});
  const [image, setImage] = useState<ImagePickerValue>({ file: null, remove: false });

  useEffect(() => {
    if (!product) return;
    setPrice(product.base_price);
    setCompareAt('');
    setStock('10');
    setActive(true);
    setPerVariant(false);
    setRows(
      product.variants.map((v) => ({
        variant_id: v.id,
        sku: v.sku,
        label: v.label,
        price: v.base_price,
        compare_at_price: '',
        stock: '10',
        is_active: true,
      })),
    );
    setCustomContent(false);
    setTitle({});
    setDescription({});
    setImage({ file: null, remove: false });
    reset();
  }, [product, reset]);

  const hasVariants = (product?.variants.length ?? 0) > 1;

  const submit = (e: FormEvent) => {
    e.preventDefault();
    if (!product || !shop) return;
    const clientErrors: Record<string, string> = perVariant ? validateVariantRows(rows) : {};
    if (!perVariant) {
      if (price === '' || Number(price) < 0) clientErrors.price = 'Enter a valid price.';
      if (compareAt !== '' && Number(compareAt) <= Number(price)) clientErrors.compare_at_price = 'Must be higher than the price.';
      if (!/^\d+$/.test(stock)) clientErrors.stock = 'Whole number ≥ 0.';
    }
    if (Object.keys(clientErrors).length) {
      setErrors(clientErrors);
      return;
    }

    // Defaults are required by the API even when overriding every variant.
    const defaultPrice = perVariant ? (rows[0]?.price ?? price) : price;
    const defaultStock = perVariant ? (rows[0]?.stock ?? stock) : stock;

    create.mutate(
      {
        slug: shop.slug,
        payload: {
          product_id: Number(product.id),
          price: defaultPrice,
          stock: Number.parseInt(defaultStock, 10),
          compare_at_price: perVariant ? undefined : compareAt || undefined,
          is_active: active,
          title: customContent ? title : undefined,
          description: customContent ? description : undefined,
          thumbnail: image.file,
          variants: perVariant ? toVariantPayload(rows) : undefined,
        },
      },
      {
        onSuccess: () => {
          toast.success(`${product.name} added to ${shop.name}`);
          onClose();
        },
        onError: handleError,
      },
    );
  };

  return (
    <Modal
      open={!!product}
      onClose={onClose}
      size="lg"
      locked={create.isPending}
      title={`Add to ${shop?.name ?? 'shop'}`}
      description="Set your price and stock. You can fine-tune everything later from Listings."
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={create.isPending}>
            Cancel
          </Button>
          <Button type="submit" form="add-to-shop" loading={create.isPending}>
            Add listing
          </Button>
        </>
      }
    >
      {product && (
        <form id="add-to-shop" onSubmit={submit} noValidate className="space-y-6">
          <div className="flex items-center gap-4 rounded-xl bg-canvas p-3">
            <ImageBox image={product.thumbnail} alt="" variant="thumb" className="size-14 shrink-0 rounded-lg border border-line" />
            <div className="min-w-0">
              <p className="truncate text-sm font-medium">{product.name}</p>
              <p className="text-[0.8125rem] text-muted">
                <span className="font-mono text-xs">{product.sku}</span> · base {formatMoney(product.base_price)} · {product.variants.length} variant
                {product.variants.length === 1 ? '' : 's'}
              </p>
            </div>
          </div>

          {errorFor('product_id') && <p className="text-sm text-danger-600">{errorFor('product_id')}</p>}

          {!perVariant && (
            <div className="grid gap-4 sm:grid-cols-3">
              <Field label="Price (৳)" required error={errorFor('price')}>
                <Input type="number" min={0} step="0.01" inputMode="decimal" value={price} onChange={(e) => { setPrice(e.target.value); clearError('price'); }} data-autofocus />
              </Field>
              <Field label="Compare-at (৳)" optional error={errorFor('compare_at_price')}>
                <Input type="number" min={0} step="0.01" inputMode="decimal" value={compareAt} onChange={(e) => { setCompareAt(e.target.value); clearError('compare_at_price'); }} />
              </Field>
              <Field label="Stock" required error={errorFor('stock')} hint={hasVariants ? 'Per variant' : undefined}>
                <Input type="number" min={0} step="1" inputMode="numeric" value={stock} onChange={(e) => { setStock(e.target.value); clearError('stock'); }} />
              </Field>
            </div>
          )}

          {hasVariants && (
            <Disclosure
              title="Customize per variant"
              description="Set a different price, stock or availability for each variant."
              open={perVariant}
              onToggle={() => {
                if (!perVariant) setRows((r) => r.map((row) => ({ ...row, price: price || row.price, stock: stock || row.stock })));
                setPerVariant((p) => !p);
              }}
            >
              <VariantPricingTable rows={rows} onChange={setRows} errors={errors} onClearError={clearError} />
            </Disclosure>
          )}

          <Disclosure
            title="Customize content"
            description="Override the title and description for your shop."
            open={customContent}
            onToggle={() => setCustomContent((c) => !c)}
          >
            <TranslatableFields
              titleField="title"
              titleLabel="Title"
              title={title}
              description={description}
              onTitle={setTitle}
              onDescription={setDescription}
              errorFor={errorFor}
              titlePlaceholder={{ en: product.name }}
            />
          </Disclosure>

          <ImagePicker
            label="Listing image"
            value={image}
            onChange={(v) => { setImage(v); clearError('thumbnail'); }}
            error={errorFor('thumbnail')}
            removable={false}
            hint="Optional — defaults to the product image."
          />

          <div className="flex items-center justify-between gap-4 rounded-xl border border-line p-4">
            <div>
              <p className="text-sm font-medium">Publish immediately</p>
              <p className="text-[0.8125rem] text-muted">Make it visible to customers right away.</p>
            </div>
            <Switch checked={active} onChange={setActive} label="Publish immediately" />
          </div>
        </form>
      )}
    </Modal>
  );
}
