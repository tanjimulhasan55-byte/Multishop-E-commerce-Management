<?php

namespace App\DTOs\Catalog;

use App\DTOs\Concerns\CleansTranslations;

final readonly class CategoryData
{
    use CleansTranslations;

    /** @param array<string, string> $name */
    public function __construct(
        public array $name,
        public ?int $parentId,
        public bool $isActive,
        public int $sortOrder,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: self::translations($data['name']) ?? [],
            parentId: filled($data['parent_id'] ?? null) ? (int) $data['parent_id'] : null,
            isActive: (bool) ($data['is_active'] ?? true),
            sortOrder: (int) ($data['sort_order'] ?? 0),
        );
    }

    public function toAttributes(): array
    {
        return [
            'name' => $this->name,
            'parent_id' => $this->parentId,
            'is_active' => $this->isActive,
            'sort_order' => $this->sortOrder,
        ];
    }
}
