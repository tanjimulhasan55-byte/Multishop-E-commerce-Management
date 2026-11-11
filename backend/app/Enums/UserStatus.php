<?php

namespace App\Enums;

enum UserStatus: string
{
    use HasLabel;

    case Active = 'active';
    case Suspended = 'suspended';

    public function color(): string
    {
        return $this === self::Active ? 'emerald' : 'rose';
    }
}
