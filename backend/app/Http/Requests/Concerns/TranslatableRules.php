<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

trait TranslatableRules
{
    /**
     * Rules for a {en, bn} translatable field. English is the required base
     * language when the field itself is required.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function translatable(string $field, bool $required = true, int $max = 150): array
    {
        return [
            $field => [$required ? 'required' : 'nullable', 'array:en,bn'],
            "{$field}.en" => [$required ? 'required' : 'nullable', 'string', "max:{$max}"],
            "{$field}.bn" => ['nullable', 'string', "max:{$max}"],
        ];
    }

    /** @return array<int, mixed> */
    protected function imageRules(): array
    {
        return [
            'nullable',
            File::image()
                ->types(['jpg', 'jpeg', 'png', 'webp'])
                ->max(2 * 1024)
                ->dimensions(Rule::dimensions()->minWidth(100)->minHeight(100)->maxWidth(5000)->maxHeight(5000)),
        ];
    }

    /** @return array<int, mixed> */
    protected function moneyRules(bool $required = true): array
    {
        return [$required ? 'required' : 'nullable', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'];
    }

    /** @return array<string, string> */
    protected function translatableAttributes(string $field, string $label): array
    {
        return [
            "{$field}.en" => "{$label} (English)",
            "{$field}.bn" => "{$label} (Bangla)",
        ];
    }
}
