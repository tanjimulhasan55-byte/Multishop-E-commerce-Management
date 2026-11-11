<?php

namespace App\DTOs\Catalog;

use App\DTOs\Concerns\CleansTranslations;
use App\Enums\ProductStatus;
use Illuminate\Http\UploadedFile;

final readonly class ProductData
{
    use CleansTranslations;

    /**
     * @param  array<string, string>  $name
     * @param  array<string, string>|null  $description
     */
    public function __construct(
        public array $name,
        public ?array $description,
        public ?int $categoryId,
        public ?string $sku,
        public string $basePrice,
        public ProductStatus $status,
        public ?UploadedFile $thumbnail = null,
        public bool $removeThumbnail = false,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: self::translations($data['name']) ?? [],
            description: self::translations($data['description'] ?? null),
            categoryId: isset($data['category_id']) ? (int) $data['category_id'] : null,
            sku: filled($data['sku'] ?? null) ? strtoupper(trim($data['sku'])) : null,
            basePrice: number_format((float) ($data['base_price'] ?? 0), 2, '.', ''),
            status: ProductStatus::tryFrom((string) ($data['status'] ?? '')) ?? ProductStatus::Active,
            thumbnail: $data['thumbnail'] ?? null,
            removeThumbnail: (bool) ($data['remove_thumbnail'] ?? false),
        );
    }

    public function toAttributes(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'category_id' => $this->categoryId,
            'sku' => $this->sku,
            'base_price' => $this->basePrice,
            'status' => $this->status,
        ];
    }
}
