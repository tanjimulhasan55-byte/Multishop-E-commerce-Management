import { Spinner } from '@/components/ui';

/** Shown while a lazily-loaded route chunk downloads. */
export function PageFallback() {
  return (
    <div className="flex min-h-[50vh] items-center justify-center text-brand-600">
      <Spinner className="size-6" label="Loading page" />
    </div>
  );
}
