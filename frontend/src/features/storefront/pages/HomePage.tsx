import { useMemo } from 'react';
import { Link } from 'react-router-dom';
import { ArrowRight, BadgeCheck, Banknote, Sparkles, Store, Truck } from 'lucide-react';
import { useCategories, useListings, useShops } from '@/api/storefront';
import { ButtonLink, Skeleton } from '@/components/ui';
import { buildCategoryTree } from '@/lib/categories';
import { ProductGrid } from '../components/ProductCard';
import { ShopCard, ShopCardSkeleton } from '../components/ShopCard';

function SectionHeader({ title, description, to, cta }: { title: string; description?: string; to: string; cta: string }) {
  return (
    <div className="mb-8 flex items-end justify-between gap-4">
      <div>
        <h2 className="text-xl font-semibold tracking-tight sm:text-2xl">{title}</h2>
        {description && <p className="mt-1 text-sm text-muted">{description}</p>}
      </div>
      <Link to={to} className="group inline-flex shrink-0 items-center gap-1 text-sm font-medium text-brand-600 hover:text-brand-700">
        {cta}
        <ArrowRight className="size-4 transition-transform duration-150 group-hover:translate-x-0.5" aria-hidden />
      </Link>
    </div>
  );
}

function Hero({ productCount, shopCount }: { productCount?: number; shopCount?: number }) {
  return (
    <section className="relative isolate overflow-hidden border-b border-line bg-surface">
      {/* Decorative backdrop */}
      <div aria-hidden className="absolute inset-0 -z-10">
        <div className="absolute -top-40 left-1/2 h-[36rem] w-[64rem] -translate-x-1/2 rounded-full bg-gradient-to-tr from-brand-200/70 via-brand-100/40 to-accent-500/20 blur-3xl" />
        <div className="absolute inset-0 bg-[linear-gradient(to_right,var(--color-line)_1px,transparent_1px),linear-gradient(to_bottom,var(--color-line)_1px,transparent_1px)] [mask-image:radial-gradient(ellipse_at_center,black_20%,transparent_70%)] bg-[size:48px_48px] opacity-50" />
      </div>
      <div className="container-page py-20 text-center sm:py-28">
        <span className="inline-flex animate-fade-in items-center gap-2 rounded-full border border-brand-200 bg-surface/80 px-3 py-1 text-xs font-medium text-brand-700 shadow-xs backdrop-blur">
          <Sparkles className="size-3.5" aria-hidden /> Independent shops · one cart
        </span>
        <h1 className="mx-auto mt-6 max-w-3xl text-4xl leading-[1.08] font-bold tracking-[-0.035em] sm:text-6xl">
          Everything local,{' '}
          <span className="bg-gradient-to-r from-brand-600 to-accent-500 bg-clip-text text-transparent">delivered to your door</span>
        </h1>
        <p className="mx-auto mt-6 max-w-xl text-base text-muted sm:text-lg">
          Shop gadgets, fashion and home essentials from trusted sellers across Bangladesh. Pay on delivery or online.
        </p>
        <div className="mt-10 flex flex-col justify-center gap-3 sm:flex-row">
          <ButtonLink to="/products" size="lg" trailingIcon={<ArrowRight className="size-4" />}>
            Start shopping
          </ButtonLink>
          <ButtonLink to="/shops" size="lg" variant="outline" leadingIcon={<Store className="size-4" />}>
            Explore shops
          </ButtonLink>
        </div>
        <dl className="mx-auto mt-14 grid max-w-lg grid-cols-3 gap-6 border-t border-line pt-8">
          {[
            { label: 'Products', value: productCount },
            { label: 'Shops', value: shopCount },
            { label: 'Currency', value: 'BDT' },
          ].map((s) => (
            <div key={s.label}>
              <dt className="text-xs text-muted">{s.label}</dt>
              <dd className="mt-1 text-xl font-semibold tracking-tight tabular-nums">
                {s.value ?? <Skeleton className="mx-auto h-6 w-10" />}
              </dd>
            </div>
          ))}
        </dl>
      </div>
    </section>
  );
}

const perks = [
  { icon: Truck, title: 'Nationwide delivery', text: 'Sellers ship to every district.' },
  { icon: Banknote, title: 'Cash on delivery', text: 'Pay when it arrives at your door.' },
  { icon: BadgeCheck, title: 'Verified shops', text: 'Every shop is reviewed by our team.' },
];

export default function HomePage() {
  const latest = useListings({ per_page: 8, sort: '-created_at' });
  const shops = useShops({ per_page: 6 });
  const categories = useCategories();
  const roots = useMemo(() => buildCategoryTree(categories.data ?? []), [categories.data]);

  return (
    <>
      <Hero productCount={latest.data?.meta.total} shopCount={shops.data?.meta.total} />

      <section className="container-page pt-14" aria-labelledby="home-categories">
        <h2 id="home-categories" className="sr-only">
          Shop by category
        </h2>
        <div className="scrollbar-none -mx-4 flex gap-2 overflow-x-auto px-4 sm:mx-0 sm:flex-wrap sm:px-0">
          {categories.isLoading
            ? Array.from({ length: 7 }, (_, i) => <Skeleton key={i} className="h-10 w-28 shrink-0 rounded-full" />)
            : roots.flatMap((root) => [
                <Link
                  key={root.id}
                  to={`/products?category=${root.slug}`}
                  className="inline-flex h-10 shrink-0 items-center rounded-full border border-line-strong bg-surface px-4 text-sm font-medium text-ink shadow-xs transition-colors duration-150 hover:border-ink"
                >
                  {root.name}
                </Link>,
                ...root.children.map((child) => (
                  <Link
                    key={child.id}
                    to={`/products?category=${child.slug}`}
                    className="inline-flex h-10 shrink-0 items-center rounded-full border border-transparent bg-subtle px-4 text-sm text-ink-soft transition-colors duration-150 hover:border-line-strong hover:bg-surface hover:text-ink"
                  >
                    {child.name}
                  </Link>
                )),
              ])}
        </div>
      </section>

      <section className="container-page pt-16" aria-labelledby="home-latest">
        <div id="home-latest">
          <SectionHeader title="New arrivals" description="Fresh from our sellers this week." to="/products" cta="View all" />
        </div>
        <ProductGrid listings={latest.data?.items} loading={latest.isLoading} skeletons={8} />
      </section>

      <section className="container-page pt-20" aria-labelledby="home-shops">
        <div id="home-shops">
          <SectionHeader title="Featured shops" description="Independent sellers we love." to="/shops" cta="All shops" />
        </div>
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {shops.isLoading
            ? Array.from({ length: 3 }, (_, i) => <ShopCardSkeleton key={i} />)
            : shops.data?.items.map((s) => <ShopCard key={s.id} shop={s} />)}
        </div>
      </section>

      <section className="container-page pt-20" aria-label="Why shop with us">
        <div className="grid gap-px overflow-hidden rounded-2xl border border-line bg-line sm:grid-cols-3">
          {perks.map(({ icon: Icon, title, text }) => (
            <div key={title} className="flex items-start gap-4 bg-surface p-6">
              <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                <Icon className="size-5" aria-hidden />
              </span>
              <div>
                <h3 className="text-sm font-semibold">{title}</h3>
                <p className="mt-0.5 text-sm text-muted">{text}</p>
              </div>
            </div>
          ))}
        </div>
      </section>
    </>
  );
}
