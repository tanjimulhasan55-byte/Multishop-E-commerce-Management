import { Link } from 'react-router-dom';
import { ArrowUpRight, MapPin, Package } from 'lucide-react';
import { ImageBox, Skeleton } from '@/components/ui';
import { pluralize } from '@/lib/format';
import type { Shop } from '@/types/models';

export function ShopLogo({ shop, className = 'size-14' }: { shop: Shop; className?: string }) {
  return shop.logo ? (
    <ImageBox image={shop.logo} alt={`${shop.name} logo`} variant="thumb" className={`rounded-xl border border-line ${className}`} />
  ) : (
    <div
      aria-hidden
      className={`flex items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-accent-500 text-lg font-bold text-white ${className}`}
    >
      {shop.name.charAt(0)}
    </div>
  );
}

export function ShopCard({ shop }: { shop: Shop }) {
  return (
    <article className="group relative flex h-full flex-col rounded-2xl border border-line bg-surface p-5 shadow-xs transition-[box-shadow,transform,border-color] duration-200 hover:-translate-y-0.5 hover:border-line-strong hover:shadow-raised">
      <div className="flex items-start justify-between gap-3">
        <ShopLogo shop={shop} />
        <ArrowUpRight className="size-5 text-faint transition-all duration-200 group-hover:-translate-y-0.5 group-hover:translate-x-0.5 group-hover:text-brand-600" aria-hidden />
      </div>
      <h3 className="mt-4 text-base font-semibold">
        <Link to={`/shops/${shop.slug}`} className="after:absolute after:inset-0 after:content-[''] focus-visible:outline-none">
          {shop.name}
        </Link>
      </h3>
      {shop.description && <p className="mt-1 line-clamp-2 text-sm text-muted">{shop.description}</p>}
      <div className="mt-auto flex flex-wrap items-center gap-x-4 gap-y-1 pt-4 text-[0.8125rem] text-muted">
        {typeof shop.listings_count === 'number' && (
          <span className="inline-flex items-center gap-1.5">
            <Package className="size-3.5" aria-hidden /> {pluralize(shop.listings_count, 'product')}
          </span>
        )}
        {shop.address && (
          <span className="inline-flex min-w-0 items-center gap-1.5">
            <MapPin className="size-3.5 shrink-0" aria-hidden /> <span className="truncate">{shop.address}</span>
          </span>
        )}
      </div>
    </article>
  );
}

export function ShopCardSkeleton() {
  return (
    <div className="rounded-2xl border border-line bg-surface p-5" aria-hidden>
      <Skeleton className="size-14 rounded-xl" />
      <Skeleton className="mt-4 h-5 w-1/2" />
      <Skeleton className="mt-2 h-3.5 w-full" />
      <Skeleton className="mt-1.5 h-3.5 w-2/3" />
      <Skeleton className="mt-5 h-3 w-1/3" />
    </div>
  );
}
