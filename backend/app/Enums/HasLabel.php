<?php

namespace App\Enums;

/**
 * Shared helpers for UI-facing status enums.
 */
trait HasLabel
{
    public function label(): string
    {
        return ucwords(str_replace('_', ' ', $this->value));
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return array<string, string> value => label */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
