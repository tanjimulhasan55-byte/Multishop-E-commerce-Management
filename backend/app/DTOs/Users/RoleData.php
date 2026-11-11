<?php

namespace App\DTOs\Users;

use Illuminate\Support\Str;

final readonly class RoleData
{
    /**
     * @param  list<string>  $permissions
     */
    public function __construct(
        public string $name,
        public array $permissions,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: Str::of($data['name'])->trim()->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->value(),
            permissions: array_values(array_unique($data['permissions'] ?? [])),
        );
    }
}
