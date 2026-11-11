import { useParams } from 'react-router-dom';
import { Mail, MapPin, Package, Phone, Store } from 'lucide-react';
import { useShop } from '@/api/storefront';
import { ButtonLink, EmptyState, Skeleton } from '@/components/ui';
import { pluralize } from '@/lib/format';
import { ListingBrowser } from '../components/ListingBrowser';
import { ShopLogo } from '../components/ShopCard';

export default function ShopPage() {
  const { slug } = useParams();
  const shop = useShop(slug);

  if (shop.isError) {
    return (
      <div className="container-page py-16">
        <EmptyState
          icon={Store}
          title="Shop not found"
          description="This shop may be closed or the link is incorrect."
          action={<ButtonLink to="/shops">Browse shops</ButtonLink>}
        />
      </div>
    );
  }

  const s = shop.data;

  return (
    <>
      <section className="border-b border-line bg-surface">
        <div aria-hidden className="h-28 bg-gradient-to-r from-brand-100 via-brand-50 to-accent-500/15 sm:h-36" />
        <div className="container-page pb-8">
          <div className="-mt-10 flex flex-col gap-5 sm:-mt-12 sm:flex-row sm:items-end">
            {s ? (
              <ShopLogo shop={s} className="size-20 shadow-raised ring-4 ring-surface sm:size-24" />
            ) : (
              <Skeleton className="size-20 rounded-xl ring-4 ring-surface sm:size-24" />
            )}
            <div className="min-w-0 flex-1">
              {s ? (
                <>
                  <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">{s.name}</h1>
                  {s.description && <p className="mt-1.5 max-w-2xl text-sm text-muted">{s.description}</p>}
                </>
              ) : (
                <>
                  <Skeleton className="h-8 w-64" />
                  <Skeleton className="mt-2 h-4 w-96 max-w-full" />
                </>
              )}
            </div>
          </div>
          {s && (
            <ul className="mt-6 flex flex-wrap gap-x-6 gap-y-2 text-[0.8125rem] text-muted">
              {typeof s.listings_count === 'number' && (
                <li className="inline-flex items-center gap-1.5">
                  <Package className="size-4" aria-hidden /> {pluralize(s.listings_count, 'product')}
                </li>
              )}
              {s.address && (
                <li className="inline-flex items-center gap-1.5">
                  <MapPin className="size-4" aria-hidden /> {s.address}
                </li>
              )}
              {s.phone && (
                <li>
                  <a href={`tel:${s.phone}`} className="inline-flex items-center gap-1.5 hover:text-ink">
                    <Phone className="size-4" aria-hidden /> {s.phone}
                  </a>
                </li>
              )}
              {s.email && (
                <li>
                  <a href={`mailto:${s.email}`} className="inline-flex items-center gap-1.5 hover:text-ink">
                    <Mail className="size-4" aria-hidden /> {s.email}
                  </a>
                </li>
              )}
            </ul>
          )}
        </div>
      </section>

      <div className="container-page py-10">
        {slug && <ListingBrowser shop={slug} showShop={false} />}
      </div>
    </>
  );
}
