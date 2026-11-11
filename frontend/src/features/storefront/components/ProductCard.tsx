import { Link } from 'react-router-dom';
import toast from 'react-hot-toast';
import { Plus, Store } from 'lucide-react';
import { Badge, ImageBox, Price, Skeleton } from '@/components/ui';
import { useCartDrawer, useCartStore } from '@/features/cart/store';
import { cn } from '@/lib/cn';
import { discountPercent } from '@/lib/format';
import type { Listing } from '@/types/models';
import { isBuyable } from '../variants';

export function ProductCard({ listing, showShop = true }: { listing: Listing; showShop?: boolean }) {
  const add = useCartStore((s) => s.add);
  const openCart = useCartDrawer((s) => s.setOpen);
  const pct = discountPercent(listing.price, listing.compare_at_price);
  const soldOut = !listing.in_stock;
  const simpleVariant = !listing.has_options ? listing.variants.find(isBuyable) : undefined;

  const quickAdd = () => {
    if (!simpleVariant) return;
    add(
      {
        variantId: simpleVariant.id,
        listingId: listing.id,
        shopId: listing.shop_id,
        shopName: listing.shop?.name ?? 'Shop',
        shopSlug: listing.shop?.slug ?? null,
        title: listing.title,
        variantLabel: null,
        sku: simpleVariant.sku,
        price: simpleVariant.price,
        compareAtPrice: simpleVariant.compare_at_price,
        image: listing.images,
        stock: simpleVariant.stock,
      },
      1,
    );
    toast.success('Added to cart');
    openCart(true);
  };

  return (
    <article className="group relative flex flex-col">
      <div className="relative z-[1] overflow-hidden rounded-2xl border border-line bg-surface shadow-xs transition-[box-shadow,transform] duration-200 ease-out group-hover:-translate-y-0.5 group-hover:shadow-raised">
        <ImageBox
          image={listing.images}
          alt={listing.title}
          aspect="aspect-[4/5]"
          imgClassName={cn('transition-transform duration-500 ease-out group-hover:scale-[1.04]', soldOut && 'opacity-60 grayscale-[35%]')}
        />
        <Link to={`/products/${listing.id}`} className="absolute inset-0" tabIndex={-1} aria-hidden="true" />
        <div className="pointer-events-none absolute top-3 left-3 flex flex-col items-start gap-1.5">
          {pct !== null && !soldOut && (
            <span className="rounded-full bg-danger-600 px-2 py-0.5 text-[0.6875rem] font-semibold text-white shadow-xs">−{pct}%</span>
          )}
          {soldOut && <Badge tone="neutral" className="bg-surface/95 backdrop-blur">Sold out</Badge>}
        </div>
        {!soldOut && (
          <div className="absolute inset-x-3 bottom-3 z-10 translate-y-2 opacity-0 transition-all duration-200 group-focus-within:translate-y-0 group-focus-within:opacity-100 group-hover:translate-y-0 group-hover:opacity-100 max-md:translate-y-0 max-md:opacity-100">
            {simpleVariant ? (
              <button
                type="button"
                onClick={quickAdd}
                className="ml-auto flex h-9 items-center gap-1.5 rounded-full bg-surface/95 px-3.5 text-[0.8125rem] font-medium text-ink shadow-raised backdrop-blur transition-colors hover:bg-ink hover:text-white"
                aria-label={`Quick add ${listing.title} to cart`}
              >
                <Plus className="size-4" /> Quick add
              </button>
            ) : (
              <Link
                to={`/products/${listing.id}`}
                className="ml-auto flex h-9 w-fit items-center gap-1.5 rounded-full bg-surface/95 px-3.5 text-[0.8125rem] font-medium text-ink shadow-raised backdrop-blur transition-colors hover:bg-ink hover:text-white"
                tabIndex={-1}
              >
                Choose options
              </Link>
            )}
          </div>
        )}
      </div>

      <div className="mt-3 flex flex-1 flex-col px-0.5">
        {showShop && listing.shop && (
          <p className="mb-1 flex items-center gap-1 truncate text-xs text-muted">
            <Store className="size-3 shrink-0" aria-hidden />
            {listing.shop.name}
          </p>
        )}
        <h3 className="line-clamp-2 text-sm font-medium text-ink">
          <Link to={`/products/${listing.id}`} className="after:absolute after:inset-0 after:z-0 after:content-[''] focus-visible:outline-none">
            {listing.title}
          </Link>
        </h3>
        <div className="mt-1.5 flex items-center justify-between gap-2">
          <Price amount={listing.price} compareAt={listing.compare_at_price} from={listing.has_options} size="sm" />
        </div>
        {listing.has_options && listing.options.length > 0 && (
          <p className="mt-1 text-xs text-muted">
            {listing.options.map((o) => `${o.values.length} ${o.name.toLowerCase()}${o.values.length === 1 ? '' : 's'}`).join(' · ')}
          </p>
        )}
      </div>
    </article>
  );
}

export function ProductCardSkeleton() {
  return (
    <div aria-hidden>
      <Skeleton className="aspect-[4/5] rounded-2xl" />
      <Skeleton className="mt-3 h-3 w-1/3" />
      <Skeleton className="mt-2 h-4 w-4/5" />
      <Skeleton className="mt-2 h-4 w-1/4" />
    </div>
  );
}

export function ProductGrid({
  listings,
  loading,
  skeletons = 8,
  showShop,
  className,
}: {
  listings?: Listing[];
  loading?: boolean;
  skeletons?: number;
  showShop?: boolean;
  className?: string;
}) {
  return (
    <div className={cn('grid grid-cols-2 gap-x-4 gap-y-8 sm:gap-x-6 md:grid-cols-3 xl:grid-cols-4', className)}>
      {loading
        ? Array.from({ length: skeletons }, (_, i) => <ProductCardSkeleton key={i} />)
        : listings?.map((l) => <ProductCard key={l.id} listing={l} showShop={showShop} />)}
    </div>
  );
}
