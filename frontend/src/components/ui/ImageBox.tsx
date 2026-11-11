import { useState } from 'react';
import { ImageOff } from 'lucide-react';
import { cn } from '@/lib/cn';
import type { ImageSet } from '@/types/models';

interface ImageBoxProps {
  image: ImageSet | null | undefined;
  alt: string;
  variant?: 'thumb' | 'medium' | 'original';
  /** Tailwind aspect class, e.g. `aspect-square`. */
  aspect?: string;
  className?: string;
  imgClassName?: string;
  eager?: boolean;
}

/** Fixed aspect-ratio image with a webp source and a graceful placeholder. */
export function ImageBox({ image, alt, variant = 'medium', aspect = 'aspect-square', className, imgClassName, eager }: ImageBoxProps) {
  const [failed, setFailed] = useState(false);
  const src = image ? image[variant] || image.original : null;

  return (
    <div className={cn('relative overflow-hidden bg-subtle', aspect, className)}>
      {src && !failed ? (
        <picture>
          {variant !== 'thumb' && image?.webp && <source srcSet={image.webp} type="image/webp" />}
          <img
            src={src}
            alt={alt}
            loading={eager ? 'eager' : 'lazy'}
            decoding="async"
            onError={() => setFailed(true)}
            className={cn('absolute inset-0 size-full object-cover', imgClassName)}
          />
        </picture>
      ) : (
        <div className="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-subtle to-line/60" role="img" aria-label={alt}>
          <ImageOff className="size-1/4 max-h-10 max-w-10 text-faint" strokeWidth={1.5} aria-hidden />
        </div>
      )}
    </div>
  );
}
