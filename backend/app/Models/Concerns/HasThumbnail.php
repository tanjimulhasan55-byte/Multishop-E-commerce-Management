<?php

namespace App\Models\Concerns;

use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Shared media setup: a single-file image collection with queued
 * thumb / medium / webp conversions.
 */
trait HasThumbnail
{
    use InteractsWithMedia;

    public const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    protected function thumbnailCollection(): string
    {
        return 'thumbnail';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection($this->thumbnailCollection())
            ->singleFile()
            ->acceptsMimeTypes(self::IMAGE_MIMES);
    }

    /**
     * Conversions run on the queue (media-library.queue_conversions_by_default
     * = true), so uploads return instantly; the seeder switches that off.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 320, 320)
            ->sharpen(8);

        $this->addMediaConversion('medium')
            ->fit(Fit::Contain, 800, 800);

        $this->addMediaConversion('webp')
            ->fit(Fit::Contain, 1200, 1200)
            ->format('webp');
    }

    /**
     * URLs for every size. Falls back to the original file while queued
     * conversions are still being generated.
     *
     * @return array{original: string, thumb: string, medium: string, webp: string}|null
     */
    public function thumbnailUrls(): ?array
    {
        $media = $this->getFirstMedia($this->thumbnailCollection());

        if ($media === null) {
            return null;
        }

        $original = $media->getFullUrl();
        $url = fn (string $c) => $media->hasGeneratedConversion($c) ? $media->getFullUrl($c) : $original;

        return [
            'original' => $original,
            'thumb' => $url('thumb'),
            'medium' => $url('medium'),
            'webp' => $url('webp'),
        ];
    }
}
