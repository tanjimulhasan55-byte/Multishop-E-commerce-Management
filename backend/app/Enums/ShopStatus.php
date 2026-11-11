<?php

namespace App\Enums;

enum ShopStatus: string
{
    use HasLabel;

    case Active = 'active';
    case Inactive = 'inactive';

    public function color(): string
    {
        return $this === self::Active ? 'emerald' : 'slate';
    }
}
