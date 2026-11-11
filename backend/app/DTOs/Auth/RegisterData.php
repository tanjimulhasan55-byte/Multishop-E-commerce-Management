<?php

namespace App\DTOs\Auth;

final readonly class RegisterData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone,
        #[\SensitiveParameter] public string $password,
    ) {}
}
