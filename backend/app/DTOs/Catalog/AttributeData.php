<?php

namespace App\DTOs\Catalog;

use App\DTOs\Concerns\CleansTranslations;
use App\Services\Catalog\SkuGenerator;

final readonly class AttributeData
{
    use CleansTranslations;

    /**
     * @param  array<string, string>  $name
     * @param  list<array{id: ?int, value: array<string, string>, code: string, color_hex: ?string, sort_order: int}>  $values
     */
    public function __construct(
        public array $name,
        public string $type,
        public int $sortOrder,
        public array $values,
    ) {}

    public static function fromArray(array $data): self
    {
        $values = collect($data['values'] ?? [])->values()->map(function (array $v, int $i) {
            $value = self::translations($v['value']) ?? [];

            return [
                'id' => filled($v['id'] ?? null) ? (int) $v['id'] : null,
                'value' => $value,
                'code' => SkuGenerator::code(filled($v['code'] ?? null) ? $v['code'] : ($value['en'] ?? 'X')),
                'color_hex' => filled($v['color_hex'] ?? null) ? strtoupper($v['color_hex']) : null,
                'sort_order' => $i,
            ];
        })->all();

        return new self(
            name: self::translations($data['name']) ?? [],
            type: $data['type'],
            sortOrder: (int) ($data['sort_order'] ?? 0),
            values: $values,
        );
    }
}
