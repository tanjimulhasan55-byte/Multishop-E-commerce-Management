import { useEffect, useState, type FormEvent } from 'react';
import toast from 'react-hot-toast';
import { asTranslations } from '@/api/jsonapi';
import { useSaveProduct } from '@/api/owner';
import { Button, Drawer, Field, ImagePicker, Input, Select, Tabs, type ImagePickerValue } from '@/components/ui';
import { useFormErrors } from '@/hooks/useFormErrors';
import type { Product, ProductStatus, Translations } from '@/types/models';
import { CategorySelect } from './CategorySelect';
import { TranslatableFields } from './TranslatableFields';
import { VariantManager } from './VariantManager';

interface ProductEditorProps {
  open: boolean;
  /** `null` = create a new product. */
  product: Product | null;
  onClose: () => void;
}

type Section = 'details' | 'variants';

export function ProductEditor({ open, product: initial, onClose }: ProductEditorProps) {
  const save = useSaveProduct();
  const { errorFor, handleError, clearError, setErrors, reset } = useFormErrors();
  const [product, setProduct] = useState<Product | null>(initial);
  const [section, setSection] = useState<Section>('details');
  const [name, setName] = useState<Translations>({});
  const [description, setDescription] = useState<Translations>({});
  const [categoryId, setCategoryId] = useState('');
  const [sku, setSku] = useState('');
  const [basePrice, setBasePrice] = useState('');
  const [status, setStatus] = useState<ProductStatus>('active');
  const [image, setImage] = useState<ImagePickerValue>({ file: null, remove: false });

  useEffect(() => {
    if (!open) return;
    setProduct(initial);
    setSection('details');
    setName(initial ? asTranslations(initial.translations.name) : {});
    setDescription(initial ? asTranslations(initial.translations.description) : {});
    setCategoryId(initial?.category_id ? String(initial.category_id) : '');
    setSku(initial?.sku ?? '');
    setBasePrice(initial?.base_price ?? '');
    setStatus(initial?.status ?? 'active');
    setImage({ file: null, remove: false });
    reset();
    // Re-initialise only when opening or switching product — not on background refetches.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [open, initial?.id]);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const clientErrors: Record<string, string> = {};
    if (!name.en?.trim()) clientErrors['name.en'] = 'English name is required.';
    if (basePrice === '' || Number(basePrice) < 0) clientErrors.base_price = 'Enter a valid price.';
    if (sku && !/^[A-Za-z0-9\-_]+$/.test(sku)) clientErrors.sku = 'Letters, numbers, dashes and underscores only.';
    if (Object.keys(clientErrors).length) {
      setErrors(clientErrors);
      return;
    }

    save.mutate(
      {
        id: product?.id,
        payload: {
          name,
          description,
          category_id: categoryId ? Number(categoryId) : null,
          sku,
          base_price: basePrice,
          status,
          thumbnail: image.file,
          remove_thumbnail: image.remove,
        },
      },
      {
        onSuccess: (saved) => {
          const created = !product;
          setProduct(saved);
          setSku(saved.sku);
          setImage({ file: null, remove: false });
          if (created) {
            toast.success('Product created — now add variants if it comes in options');
            setSection('variants');
          } else {
            toast.success('Product saved');
          }
        },
        onError: handleError,
      },
    );
  };

  const isEdit = !!product;

  return (
    <Drawer
      open={open}
      onClose={onClose}
      width="lg"
      locked={save.isPending}
      title={isEdit ? 'Edit product' : 'New product'}
      description={isEdit ? product.name : 'Products join the shared catalog, so any shop can sell them.'}
      footer={
        section === 'details' ? (
          <>
            <Button variant="outline" onClick={onClose} disabled={save.isPending}>
              {isEdit ? 'Close' : 'Cancel'}
            </Button>
            <Button type="submit" form="product-editor" loading={save.isPending}>
              {isEdit ? 'Save product' : 'Create product'}
            </Button>
          </>
        ) : (
          <Button variant="outline" onClick={onClose}>
            Done
          </Button>
        )
      }
    >
      {isEdit && (
        <Tabs
          className="mb-6"
          label="Product sections"
          value={section}
          onChange={setSection}
          items={[
            { value: 'details', label: 'Details' },
            { value: 'variants', label: 'Variants', count: product.variants.length },
          ]}
        />
      )}

      {section === 'variants' && product ? (
        <VariantManager product={product} onProductChange={setProduct} />
      ) : (
        <form id="product-editor" onSubmit={submit} noValidate className="space-y-6">
          <TranslatableFields
            titleField="name"
            titleLabel="Name"
            titleRequired
            title={name}
            description={description}
            onTitle={(v) => {
              setName(v);
              clearError('name.en');
              clearError('name');
            }}
            onDescription={setDescription}
            errorFor={errorFor}
          />

          <div className="grid gap-5 sm:grid-cols-2">
            <Field label="Category" optional error={errorFor('category_id')}>
              <CategorySelect emptyLabel="Uncategorised" value={categoryId} onChange={(e) => { setCategoryId(e.target.value); clearError('category_id'); }} />
            </Field>
            <Field label="Status" error={errorFor('status')}>
              <Select value={status} onChange={(e) => setStatus(e.target.value as ProductStatus)}>
                <option value="active">Active — available to shops</option>
                <option value="draft">Draft — hidden</option>
                <option value="archived">Archived</option>
              </Select>
            </Field>
            <Field label="SKU" optional={!isEdit} error={errorFor('sku')} hint={isEdit ? undefined : 'Leave empty to auto-generate.'}>
              <Input className="font-mono uppercase" value={sku} onChange={(e) => { setSku(e.target.value.toUpperCase()); clearError('sku'); }} placeholder="e.g. TSHIRT-01" />
            </Field>
            <Field label="Base price (৳)" required error={errorFor('base_price')} hint="Suggested price; each shop sets its own.">
              <Input type="number" min={0} step="0.01" inputMode="decimal" value={basePrice} onChange={(e) => { setBasePrice(e.target.value); clearError('base_price'); }} />
            </Field>
          </div>

          <ImagePicker
            label="Thumbnail"
            currentUrl={product?.thumbnail?.thumb}
            value={image}
            onChange={(v) => {
              setImage(v);
              clearError('thumbnail');
            }}
            error={errorFor('thumbnail')}
          />
        </form>
      )}
    </Drawer>
  );
}
