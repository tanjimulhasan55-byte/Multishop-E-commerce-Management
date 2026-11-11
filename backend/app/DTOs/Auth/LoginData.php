<?php

namespace App\DTOs\Auth;

final readonly class LoginData
{
    public function __construct(
        public string $email,
        #[\SensitiveParameter] public string $password,
    ) {}

    /** @return array{email: string, password: string} */
    public function credentials(): array
    {
        return ['email' => $this->email, 'password' => $this->password];
    }
}
