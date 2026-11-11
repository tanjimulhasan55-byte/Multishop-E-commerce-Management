import { useSearchParams } from 'react-router-dom';
import { useCategories } from '@/api/storefront';
import { PageHeader } from '@/components/ui';
import { ListingBrowser } from '../components/ListingBrowser';

export default function ProductsPage() {
  const [params] = useSearchParams();
  const categories = useCategories();
  const search = params.get('search');
  const category = categories.data?.find((c) => c.slug === params.get('category'));

  const title = search ? `Results for “${search}”` : (category?.name ?? 'All products');

  return (
    <div className="container-page py-8 sm:py-12">
      <PageHeader
        eyebrow={category && search ? category.name : 'Browse'}
        title={title}
        description="Products from every shop on MultiShop. Filter by category, price and availability."
      />
      <div className="mt-8">
        <ListingBrowser />
      </div>
    </div>
  );
}
