<?php

namespace App\DTOs\Account;

final readonly class ProfileData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone,
    ) {}
}
