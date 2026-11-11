import { useEffect, useState, type FormEvent } from 'react';
import toast from 'react-hot-toast';
import { asTranslations } from '@/api/jsonapi';
import { useUpdateListing } from '@/api/owner';
import { Badge, Button, Drawer, ImagePicker, Switch, type ImagePickerValue } from '@/components/ui';
import { useFormErrors } from '@/hooks/useFormErrors';
import type { Listing, Translations } from '@/types/models';
import { TranslatableFields } from './TranslatableFields';
import { toVariantPayload, validateVariantRows, VariantPricingTable, type VariantRow } from './VariantPricingTable';

export function listingVariantRows(listing: Listing): VariantRow[] {
  return listing.variants.map((v) => ({
    variant_id: v.variant_id,
    sku: v.sku,
    label: v.label,
    price: v.price,
    compare_at_price: v.compare_at_price ?? '',
    stock: String(v.stock),
    is_active: v.is_active,
  }));
}

interface ListingEditorProps {
  listing: Listing | null;
  onClose: () => void;
}

/** Drawer to edit a listing's overrides, thumbnail, visibility and per-variant pricing. */
export function ListingEditor({ listing, onClose }: ListingEditorProps) {
  const update = useUpdateListing();
  const { errors, errorFor, handleError, clearError, setErrors, reset } = useFormErrors();
  const [title, setTitle] = useState<Translations>({});
  const [description, setDescription] = useState<Translations>({});
  const [active, setActive] = useState(true);
  const [image, setImage] = useState<ImagePickerValue>({ file: null, remove: false });
  const [rows, setRows] = useState<VariantRow[]>([]);

  useEffect(() => {
    if (!listing) return;
    setTitle(asTranslations(listing.overrides?.title));
    setDescription(asTranslations(listing.overrides?.description));
    setActive(listing.is_active);
    setImage({ file: null, remove: false });
    setRows(listingVariantRows(listing));
    reset();
  }, [listing, reset]);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    if (!listing) return;
    const clientErrors = validateVariantRows(rows);
    if (Object.keys(clientErrors).length) {
      setErrors(clientErrors);
      return;
    }
    update.mutate(
      {
        id: listing.id,
        payload: {
          // Overrides are replaced wholesale — always send both.
          title,
          description,
          is_active: active,
          thumbnail: image.file,
          remove_thumbnail: image.remove,
          variants: toVariantPayload(rows),
        },
      },
      {
        onSuccess: () => {
          toast.success('Listing saved');
          onClose();
        },
        onError: handleError,
      },
    );
  };

  return (
    <Drawer
      open={!!listing}
      onClose={onClose}
      width="lg"
      locked={update.isPending}
      title="Edit listing"
      description={listing?.title}
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={update.isPending}>
            Cancel
          </Button>
          <Button type="submit" form="listing-editor" loading={update.isPending}>
            Save listing
          </Button>
        </>
      }
    >
      {listing && (
        <form id="listing-editor" onSubmit={submit} noValidate className="space-y-8">
          <section className="flex items-center justify-between gap-4 rounded-xl border border-line bg-canvas p-4">
            <div>
              <p className="text-sm font-medium">Visible in store</p>
              <p className="text-[0.8125rem] text-muted">Inactive listings are hidden from customers.</p>
            </div>
            <Switch checked={active} onChange={setActive} label="Listing active" />
          </section>

          <section className="space-y-3">
            <div className="flex items-center gap-2">
              <h3 className="text-sm font-semibold">Pricing & stock</h3>
              <Badge>{listing.variants.length} variant{listing.variants.length === 1 ? '' : 's'}</Badge>
            </div>
            <VariantPricingTable rows={rows} onChange={setRows} errors={errors} onClearError={clearError} />
          </section>

          <section className="space-y-3">
            <h3 className="text-sm font-semibold">Storefront content</h3>
            <TranslatableFields
              titleField="title"
              titleLabel="Title override"
              title={title}
              description={description}
              onTitle={(v) => {
                setTitle(v);
                clearError('title.en');
                clearError('title.bn');
              }}
              onDescription={setDescription}
              errorFor={errorFor}
              titlePlaceholder={{ en: 'Leave empty to use the product name', bn: 'খালি রাখলে পণ্যের নাম দেখাবে' }}
              descriptionPlaceholder={{ en: 'Leave empty to use the product description' }}
            />
          </section>

          <section>
            <ImagePicker
              label="Listing image"
              currentUrl={listing.has_own_thumbnail ? listing.images?.thumb : null}
              value={image}
              onChange={(v) => {
                setImage(v);
                clearError('thumbnail');
              }}
              error={errorFor('thumbnail')}
              hint={listing.has_own_thumbnail ? undefined : 'Using the product image. Upload to override it for this shop.'}
            />
          </section>
        </form>
      )}
    </Drawer>
  );
}
