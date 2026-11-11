import { useEffect, useMemo, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import toast from 'react-hot-toast';
import { ChevronRight, PackageX, RotateCcw, ShieldCheck, ShoppingBag, Store, Truck } from 'lucide-react';
import { useListing, useListings } from '@/api/storefront';
import { Badge, Button, ButtonLink, EmptyState, ImageBox, Price, QuantityStepper, Skeleton, SkeletonText } from '@/components/ui';
import { MAX_QTY, useCartDrawer, useCartStore } from '@/features/cart/store';
import { discountPercent } from '@/lib/format';
import { ProductGrid } from '../components/ProductCard';
import { VariantPicker } from '../components/VariantPicker';
import { initialSelection, isBuyable, resolveVariant, selectValue, type Selection } from '../variants';

const LOW_STOCK = 5;

function ProductSkeleton() {
  return (
    <div className="grid gap-10 lg:grid-cols-2 lg:gap-16" aria-busy>
      <Skeleton className="aspect-square rounded-2xl" />
      <div>
        <Skeleton className="h-4 w-32" />
        <Skeleton className="mt-4 h-9 w-3/4" />
        <Skeleton className="mt-4 h-8 w-40" />
        <SkeletonText lines={4} className="mt-8" />
        <Skeleton className="mt-8 h-12 w-full rounded-xl" />
      </div>
    </div>
  );
}

export default function ProductPage() {
  const { id } = useParams();
  const query = useListing(id);
  const listing = query.data;

  const [selection, setSelection] = useState<Selection>({});
  const [quantity, setQuantity] = useState(1);
  const add = useCartStore((s) => s.add);
  const inCart = useCartStore((s) => s.items);
  const openCart = useCartDrawer((s) => s.setOpen);

  useEffect(() => {
    if (listing) setSelection(initialSelection(listing.options, listing.variants));
    setQuantity(1);
  }, [listing]);

  const variant = useMemo(
    () => (listing ? resolveVariant(listing.options, listing.variants, selection) : undefined),
    [listing, selection],
  );

  const related = useListings({ shop: listing?.shop?.slug, per_page: 5, sort: '-created_at' }, { enabled: !!listing?.shop?.slug });
  const relatedItems = related.data?.items.filter((l) => l.id !== listing?.id).slice(0, 4);

  if (query.isError) {
    return (
      <div className="container-page py-16">
        <EmptyState
          icon={PackageX}
          title="Product unavailable"
          description="This product may have been removed or is no longer sold."
          action={<ButtonLink to="/products">Browse products</ButtonLink>}
        />
      </div>
    );
  }

  const buyable = !!variant && isBuyable(variant);
  const alreadyInCart = variant ? (inCart.find((i) => i.variantId === variant.id)?.quantity ?? 0) : 0;
  const maxQty = variant ? Math.max(1, Math.min(variant.stock - alreadyInCart, MAX_QTY)) : 1;
  const price = variant?.price ?? listing?.price ?? '0';
  const compareAt = variant ? variant.compare_at_price : (listing?.compare_at_price ?? null);
  const pct = discountPercent(price, compareAt);

  const addToCart = () => {
    if (!listing || !variant) return;
    add(
      {
        variantId: variant.id,
        listingId: listing.id,
        shopId: listing.shop_id,
        shopName: listing.shop?.name ?? 'Shop',
        shopSlug: listing.shop?.slug ?? null,
        title: listing.title,
        variantLabel: variant.label,
        sku: variant.sku,
        price: variant.price,
        compareAtPrice: variant.compare_at_price,
        image: listing.images,
        stock: variant.stock,
      },
      quantity,
    );
    toast.success(`Added ${quantity} × ${listing.title}${variant.label ? ` (${variant.label})` : ''}`);
    openCart(true);
    setQuantity(1);
  };

  return (
    <div className="container-page py-6 sm:py-10">
      <nav aria-label="Breadcrumb" className="mb-6 flex items-center gap-1.5 text-[0.8125rem] text-muted">
        <Link to="/products" className="hover:text-ink">
          Products
        </Link>
        {listing?.category && (
          <>
            <ChevronRight className="size-3.5" aria-hidden />
            <span>{listing.category}</span>
          </>
        )}
        {listing && (
          <>
            <ChevronRight className="size-3.5" aria-hidden />
            <span className="truncate text-ink" aria-current="page">
              {listing.title}
            </span>
          </>
        )}
      </nav>

      {!listing ? (
        <ProductSkeleton />
      ) : (
        <div className="grid gap-10 lg:grid-cols-2 lg:gap-16">
          {/* Gallery */}
          <div className="lg:sticky lg:top-24 lg:self-start">
            <div className="relative overflow-hidden rounded-2xl border border-line bg-surface shadow-card">
              <ImageBox image={listing.images} alt={listing.title} variant="original" aspect="aspect-square" eager />
              {pct !== null && (
                <span className="absolute top-4 left-4 rounded-full bg-danger-600 px-2.5 py-1 text-xs font-semibold text-white shadow-xs">
                  −{pct}%
                </span>
              )}
            </div>
          </div>

          {/* Buy box */}
          <div className="lg:py-2">
            {listing.shop && (
              <Link
                to={`/shops/${listing.shop.slug}`}
                className="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1 text-xs font-medium text-brand-700 transition-colors hover:bg-brand-100"
              >
                <Store className="size-3.5" aria-hidden /> {listing.shop.name}
              </Link>
            )}
            <h1 className="mt-4 text-2xl leading-tight font-semibold tracking-tight sm:text-[2rem]">{listing.title}</h1>

            <div className="mt-4 flex flex-wrap items-center gap-3">
              <Price amount={price} compareAt={compareAt} size="xl" from={listing.has_options && !variant} />
            </div>

            <div className="mt-3 flex flex-wrap items-center gap-2 text-[0.8125rem]">
              {variant ? (
                buyable ? (
                  variant.stock <= LOW_STOCK ? (
                    <Badge tone="warning" dot>
                      Only {variant.stock} left
                    </Badge>
                  ) : (
                    <Badge tone="success" dot>
                      In stock
                    </Badge>
                  )
                ) : (
                  <Badge tone="danger" dot>
                    Out of stock
                  </Badge>
                )
              ) : (
                <Badge tone="neutral">Select options</Badge>
              )}
              <span className="text-muted">
                SKU <span className="font-mono text-xs text-ink-soft">{variant?.sku ?? listing.sku}</span>
              </span>
            </div>

            {listing.description && (
              <p className="mt-6 text-[0.9375rem] leading-relaxed whitespace-pre-line text-ink-soft">{listing.description}</p>
            )}

            <div className="mt-8 space-y-6 border-t border-line pt-8">
              {listing.has_options && listing.options.length > 0 && (
                <VariantPicker
                  options={listing.options}
                  variants={listing.variants}
                  selection={selection}
                  onSelect={(attr, value) => {
                    setSelection((s) => selectValue(listing.options, listing.variants, s, attr, value));
                    setQuantity(1);
                  }}
                />
              )}

              <div>
                <p className="mb-2.5 text-[0.8125rem] font-medium text-ink-soft">Quantity</p>
                <div className="flex flex-col gap-3 sm:flex-row">
                  <QuantityStepper value={quantity} onChange={setQuantity} max={maxQty} disabled={!buyable} />
                  <Button
                    size="lg"
                    className="flex-1"
                    disabled={!variant || !buyable || variant.stock - alreadyInCart <= 0}
                    onClick={addToCart}
                    leadingIcon={<ShoppingBag className="size-5" />}
                  >
                    {!variant ? 'Select options' : !buyable ? 'Sold out' : variant.stock - alreadyInCart <= 0 ? 'All stock in cart' : 'Add to cart'}
                  </Button>
                </div>
                {alreadyInCart > 0 && (
                  <p className="mt-2 text-[0.8125rem] text-muted">
                    {alreadyInCart} already in your cart ·{' '}
                    <Link to="/cart" className="font-medium text-brand-600 hover:text-brand-700">
                      View cart
                    </Link>
                  </p>
                )}
              </div>
            </div>

            <ul className="mt-8 grid gap-3 rounded-2xl border border-line bg-surface p-5 text-[0.8125rem] sm:grid-cols-3">
              <li className="flex items-center gap-2.5 text-ink-soft">
                <Truck className="size-5 shrink-0 text-brand-600" aria-hidden /> Ships from the seller
              </li>
              <li className="flex items-center gap-2.5 text-ink-soft">
                <ShieldCheck className="size-5 shrink-0 text-success-600" aria-hidden /> Secure checkout
              </li>
              <li className="flex items-center gap-2.5 text-ink-soft">
                <RotateCcw className="size-5 shrink-0 text-accent-500" aria-hidden /> Cash on delivery
              </li>
            </ul>
          </div>
        </div>
      )}

      {relatedItems && relatedItems.length > 0 && (
        <section className="mt-20" aria-labelledby="related">
          <h2 id="related" className="mb-6 text-xl font-semibold tracking-tight">
            More from {listing?.shop?.name}
          </h2>
          <ProductGrid listings={relatedItems} showShop={false} />
        </section>
      )}
    </div>
  );
}
