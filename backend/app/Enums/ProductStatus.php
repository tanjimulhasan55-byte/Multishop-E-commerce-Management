<?php

namespace App\Enums;

enum ProductStatus: string
{
    use HasLabel;

    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'amber',
            self::Active => 'emerald',
            self::Archived => 'slate',
        };
    }
}
