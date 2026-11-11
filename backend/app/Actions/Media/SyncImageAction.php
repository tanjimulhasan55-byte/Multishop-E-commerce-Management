<?php

namespace App\Actions\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;

/**
 * Attach, replace or remove the single image of a media collection.
 * Stored file names are random UUIDs — client-supplied names are never
 * used on disk.
 */
final class SyncImageAction
{
    public function execute(HasMedia $model, ?UploadedFile $file, bool $remove, string $collection): void
    {
        if ($file !== null) {
            $model->addMedia($file)
                ->usingName(Str::limit(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 100, ''))
                ->usingFileName(Str::uuid().'.'.($file->guessExtension() ?: 'jpg'))
                ->toMediaCollection($collection);

            return;
        }

        if ($remove) {
            $model->clearMediaCollection($collection);
        }
    }
}
