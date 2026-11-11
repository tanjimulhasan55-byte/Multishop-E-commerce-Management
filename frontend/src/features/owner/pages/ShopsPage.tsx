import { useNavigate } from 'react-router-dom';
import { CheckCircle2, ExternalLink, Mail, MapPin, Package, Phone, Store } from 'lucide-react';
import { Alert, Badge, Button, ButtonLink, Card, EmptyState, PageHeader, Skeleton } from '@/components/ui';
import { ShopLogo } from '@/features/storefront/components/ShopCard';
import { cn } from '@/lib/cn';
import { pluralize } from '@/lib/format';
import { useMyShops } from '@/api/owner';
import { useCurrentShop } from '../useCurrentShop';

export default function OwnerShopsPage() {
  const query = useMyShops();
  const { shop: current, setShopSlug } = useCurrentShop();
  const navigate = useNavigate();

  return (
    <div className="space-y-8">
      <PageHeader
        eyebrow="Selling"
        title="My shops"
        description="Pick the shop you want to manage. Listings and orders follow your selection."
      />

      {query.isError && <Alert tone="danger">{query.error.message}</Alert>}

      {query.isLoading ? (
        <div className="grid gap-4 md:grid-cols-2">
          {Array.from({ length: 2 }, (_, i) => (
            <Skeleton key={i} className="h-56 rounded-2xl" />
          ))}
        </div>
      ) : query.data?.length === 0 ? (
        <Card>
          <EmptyState icon={Store} title="No shops yet" description="An administrator assigns shops to owners. Contact support to open your shop." />
        </Card>
      ) : (
        <div className="grid gap-4 md:grid-cols-2">
          {query.data?.map((shop) => {
            const selected = current?.slug === shop.slug;
            return (
              <Card
                key={shop.id}
                className={cn('flex flex-col p-5 transition-[box-shadow,border-color] duration-200 sm:p-6', selected && 'border-brand-300 ring-4 ring-brand-100')}
              >
                <div className="flex items-start gap-4">
                  <ShopLogo shop={shop} className="size-14" />
                  <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-2">
                      <h2 className="text-base font-semibold">{shop.name}</h2>
                      <Badge tone={shop.status === 'active' ? 'success' : 'neutral'} dot>
                        {shop.status}
                      </Badge>
                    </div>
                    {shop.description && <p className="mt-1 line-clamp-2 text-sm text-muted">{shop.description}</p>}
                  </div>
                </div>
                <ul className="mt-5 grid gap-2 text-[0.8125rem] text-ink-soft sm:grid-cols-2">
                  <li className="flex items-center gap-2">
                    <Package className="size-4 text-muted" aria-hidden /> {pluralize(shop.listings_count ?? 0, 'listing')}
                  </li>
                  {shop.phone && (
                    <li className="flex items-center gap-2">
                      <Phone className="size-4 text-muted" aria-hidden /> {shop.phone}
                    </li>
                  )}
                  {shop.email && (
                    <li className="flex min-w-0 items-center gap-2">
                      <Mail className="size-4 shrink-0 text-muted" aria-hidden /> <span className="truncate">{shop.email}</span>
                    </li>
                  )}
                  {shop.address && (
                    <li className="flex min-w-0 items-center gap-2">
                      <MapPin className="size-4 shrink-0 text-muted" aria-hidden /> <span className="truncate">{shop.address}</span>
                    </li>
                  )}
                </ul>
                <div className="mt-6 flex flex-wrap items-center gap-2 border-t border-line pt-4">
                  {selected ? (
                    <span className="inline-flex items-center gap-1.5 text-sm font-medium text-brand-700">
                      <CheckCircle2 className="size-4" aria-hidden /> Currently managing
                    </span>
                  ) : (
                    <Button size="sm" onClick={() => setShopSlug(shop.slug)}>
                      Manage this shop
                    </Button>
                  )}
                  <div className="ml-auto flex gap-1">
                    <Button
                      size="sm"
                      variant="ghost"
                      onClick={() => {
                        setShopSlug(shop.slug);
                        navigate('/owner/listings');
                      }}
                    >
                      Listings
                    </Button>
                    <ButtonLink to={`/shops/${shop.slug}`} size="sm" variant="ghost" trailingIcon={<ExternalLink className="size-3.5" />}>
                      Storefront
                    </ButtonLink>
                  </div>
                </div>
              </Card>
            );
          })}
        </div>
      )}
    </div>
  );
}
