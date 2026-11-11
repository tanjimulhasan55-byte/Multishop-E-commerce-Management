import { Compass } from 'lucide-react';
import { ButtonLink, EmptyState } from '@/components/ui';

export default function NotFoundPage() {
  return (
    <div className="container-page py-16">
      <EmptyState
        icon={Compass}
        title="Page not found"
        description="The page you’re looking for doesn’t exist or has moved."
        action={
          <>
            <ButtonLink to="/">Go home</ButtonLink>
            <ButtonLink to="/products" variant="outline">
              Browse products
            </ButtonLink>
          </>
        }
      />
    </div>
  );
}
