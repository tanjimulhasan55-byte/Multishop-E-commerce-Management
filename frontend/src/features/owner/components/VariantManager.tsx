import { useEffect, useMemo, useState } from 'react';
import toast from 'react-hot-toast';
import { Check, Layers, Save, Sparkles, Trash2 } from 'lucide-react';
import { errorMessage, toApiError } from '@/api/errors';
import { useAttributes, useDeleteVariant, useGenerateVariants, useUpdateVariant } from '@/api/owner';
import { Badge, Button, Field, Input, Skeleton, useConfirm } from '@/components/ui';
import { cn } from '@/lib/cn';
import type { Product, ProductVariant } from '@/types/models';

interface VariantManagerProps {
  product: Product;
  onProductChange: (product: Product) => void;
}

function VariantRowEditor({ variant, onSaved, onDeleted, canDelete }: {
  variant: ProductVariant;
  onSaved: (v: ProductVariant) => void;
  onDeleted: () => void;
  canDelete: boolean;
}) {
  const [sku, setSku] = useState(variant.sku);
  const [price, setPrice] = useState(variant.base_price);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const save = useUpdateVariant();
  const remove = useDeleteVariant();
  const confirm = useConfirm();

  useEffect(() => {
    setSku(variant.sku);
    setPrice(variant.base_price);
  }, [variant.sku, variant.base_price]);

  const dirty = sku !== variant.sku || Number(price) !== Number(variant.base_price);

  const onSave = () =>
    save.mutate(
      { id: variant.id, sku: sku.trim().toUpperCase(), base_price: price },
      {
        onSuccess: () => {
          setErrors({});
          onSaved({ ...variant, sku: sku.trim().toUpperCase(), base_price: price });
          toast.success('Variant saved');
        },
        onError: (e) => {
          const err = toApiError(e);
          setErrors(err.fieldErrors);
          if (!err.isValidation) toast.error(err.message);
        },
      },
    );

  const onDelete = async () => {
    const ok = await confirm({
      title: `Delete variant ${variant.label ?? variant.sku}?`,
      description: 'It will be removed from every shop listing this product.',
      confirmLabel: 'Delete variant',
    });
    if (!ok) return;
    remove.mutate(variant.id, {
      onSuccess: () => {
        onDeleted();
        toast.success('Variant deleted');
      },
      onError: (e) => toast.error(errorMessage(e)),
    });
  };

  return (
    <tr className="align-top">
      <td className="px-3 py-2.5">
        <div className="flex items-center gap-2 pt-2">
          <span className="font-medium">{variant.label ?? 'Default'}</span>
          {variant.is_default && <Badge>Default</Badge>}
        </div>
      </td>
      <td className="px-2 py-2.5">
        <Input
          className="h-9 font-mono text-xs uppercase"
          value={sku}
          onChange={(e) => setSku(e.target.value)}
          aria-label={`SKU for ${variant.label ?? 'default variant'}`}
          aria-invalid={!!errors.sku || undefined}
        />
        {errors.sku && <p className="mt-1 text-xs text-danger-600">{errors.sku}</p>}
      </td>
      <td className="w-36 px-2 py-2.5">
        <Input
          type="number"
          min={0}
          step="0.01"
          className="h-9 text-[0.8125rem]"
          value={price}
          onChange={(e) => setPrice(e.target.value)}
          aria-label={`Base price for ${variant.label ?? 'default variant'}`}
          aria-invalid={!!errors.base_price || undefined}
        />
        {errors.base_price && <p className="mt-1 text-xs text-danger-600">{errors.base_price}</p>}
      </td>
      <td className="px-3 py-2.5 text-right whitespace-nowrap">
        <Button size="sm" variant={dirty ? 'primary' : 'ghost'} iconOnly onClick={onSave} disabled={!dirty} loading={save.isPending} aria-label="Save variant">
          <Save className="size-4" />
        </Button>
        {canDelete && (
          <Button size="sm" variant="danger-ghost" iconOnly onClick={onDelete} loading={remove.isPending} aria-label="Delete variant">
            <Trash2 className="size-4" />
          </Button>
        )}
      </td>
    </tr>
  );
}

/** Pick attribute values → generate every combination → edit SKU/price per variant. */
export function VariantManager({ product, onProductChange }: VariantManagerProps) {
  const attributes = useAttributes();
  const generate = useGenerateVariants();
  const existing = useMemo(() => new Set(product.options.flatMap((o) => o.values.map((v) => v.id))), [product.options]);
  const [selected, setSelected] = useState<Set<number>>(existing);
  const [basePrice, setBasePrice] = useState('');
  const [error, setError] = useState<string | null>(null);

  useEffect(() => setSelected(new Set(existing)), [existing]);

  const toggle = (id: number) =>
    setSelected((s) => {
      const next = new Set(s);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });

  // Number of combinations the selection will produce.
  const combos = useMemo(() => {
    const counts = (attributes.data ?? []).map((a) => a.values.filter((v) => selected.has(v.id)).length).filter((n) => n > 0);
    return counts.length ? counts.reduce((a, b) => a * b, 1) : 0;
  }, [attributes.data, selected]);

  const onGenerate = () => {
    setError(null);
    generate.mutate(
      { id: product.id, valueIds: [...selected], basePrice: basePrice || undefined },
      {
        onSuccess: (p) => {
          onProductChange(p);
          toast.success(`${p.variants.length} variants ready`);
        },
        onError: (e) => {
          const err = toApiError(e);
          setError(err.fieldErrors.attribute_value_ids ?? err.fieldErrors.base_price ?? err.message);
        },
      },
    );
  };

  const patchVariant = (v: ProductVariant) => onProductChange({ ...product, variants: product.variants.map((x) => (x.id === v.id ? v : x)) });
  const dropVariant = (id: number) => onProductChange({ ...product, variants: product.variants.filter((x) => x.id !== id) });

  return (
    <div className="space-y-6">
      <div className="space-y-4 rounded-xl border border-line p-4">
        <div className="flex items-start gap-3">
          <span className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
            <Layers className="size-4" aria-hidden />
          </span>
          <div>
            <p className="text-sm font-medium">Options</p>
            <p className="text-[0.8125rem] text-muted">Pick the values this product comes in. Each combination becomes a variant with its own SKU.</p>
          </div>
        </div>

        {attributes.isLoading ? (
          <div className="space-y-3">
            <Skeleton className="h-10" />
            <Skeleton className="h-10" />
          </div>
        ) : (
          attributes.data?.map((attr) => (
            <fieldset key={attr.id}>
              <legend className="mb-2 text-[0.8125rem] font-medium text-ink-soft">{attr.name}</legend>
              <div className="flex flex-wrap gap-2">
                {attr.values.map((v) => {
                  const on = selected.has(v.id);
                  return (
                    <button
                      key={v.id}
                      type="button"
                      aria-pressed={on}
                      onClick={() => toggle(v.id)}
                      className={cn(
                        'inline-flex h-8 items-center gap-2 rounded-full border pr-3 text-[0.8125rem] font-medium transition-colors duration-150',
                        attr.type === 'color' ? 'pl-1.5' : 'pl-3',
                        on ? 'border-brand-500 bg-brand-50 text-brand-800' : 'border-line-strong text-ink-soft hover:border-faint',
                      )}
                    >
                      {attr.type === 'color' && (
                        <span className="size-5 rounded-full border border-black/10" style={{ backgroundColor: v.color_hex ?? undefined }} aria-hidden />
                      )}
                      {v.value}
                      {on && <Check className="size-3.5" aria-hidden />}
                    </button>
                  );
                })}
              </div>
            </fieldset>
          ))
        )}

        <div className="flex flex-col gap-3 border-t border-line pt-4 sm:flex-row sm:items-end">
          <Field label="Price for new variants (৳)" optional hint="Defaults to the product base price." className="sm:w-56">
            <Input type="number" min={0} step="0.01" value={basePrice} onChange={(e) => setBasePrice(e.target.value)} placeholder={product.base_price} />
          </Field>
          <Button onClick={onGenerate} loading={generate.isPending} disabled={selected.size === 0} leadingIcon={<Sparkles className="size-4" />} className="sm:mb-6">
            Generate {combos > 0 ? `${combos} variant${combos === 1 ? '' : 's'}` : 'variants'}
          </Button>
        </div>
        {error && <p className="text-[0.8125rem] text-danger-600" role="alert">{error}</p>}
      </div>

      <div className="overflow-hidden rounded-xl border border-line">
        <div className="overflow-x-auto">
          <table className="w-full min-w-[34rem] text-sm">
            <thead className="bg-canvas">
              <tr className="text-left text-xs font-medium tracking-wide text-muted uppercase">
                <th scope="col" className="px-3 py-2">Variant</th>
                <th scope="col" className="px-2 py-2">SKU</th>
                <th scope="col" className="px-2 py-2">Base price</th>
                <th scope="col" className="px-3 py-2 text-right"><span className="sr-only">Actions</span></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-line">
              {product.variants.map((v) => (
                <VariantRowEditor
                  key={v.id}
                  variant={v}
                  canDelete={product.variants.length > 1}
                  onSaved={patchVariant}
                  onDeleted={() => dropVariant(v.id)}
                />
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
