<?php

namespace App\DTOs\Users;

use App\Enums\UserStatus;

final readonly class UserData
{
    /**
     * @param  list<string>  $roles  role names to sync
     */
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone,
        #[\SensitiveParameter] public ?string $password,
        public UserStatus $status,
        public array $roles,
    ) {}

    /** @param list<string>|null $forceRoles roles imposed by the endpoint (e.g. shop owners API) */
    public static function fromArray(array $data, ?array $forceRoles = null): self
    {
        return new self(
            name: trim($data['name']),
            email: strtolower(trim($data['email'])),
            phone: filled($data['phone'] ?? null) ? trim($data['phone']) : null,
            password: filled($data['password'] ?? null) ? $data['password'] : null,
            status: UserStatus::tryFrom((string) ($data['status'] ?? '')) ?? UserStatus::Active,
            roles: array_values(array_unique($forceRoles ?? ($data['roles'] ?? []))),
        );
    }
}
