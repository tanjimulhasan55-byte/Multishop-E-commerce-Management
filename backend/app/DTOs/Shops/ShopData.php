<?php

namespace App\DTOs\Shops;

use App\DTOs\Concerns\CleansTranslations;
use App\Enums\ShopStatus;
use Illuminate\Http\UploadedFile;

final readonly class ShopData
{
    use CleansTranslations;

    /**
     * @param  array<string, string>  $name
     * @param  array<string, string>|null  $description
     */
    public function __construct(
        public array $name,
        public ?array $description,
        public ?int $ownerId,
        public ?string $email,
        public ?string $phone,
        public ?string $address,
        public ShopStatus $status,
        public ?UploadedFile $logo = null,
        public bool $removeLogo = false,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: self::translations($data['name']) ?? [],
            description: self::translations($data['description'] ?? null),
            ownerId: isset($data['owner_id']) ? (int) $data['owner_id'] : null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            address: $data['address'] ?? null,
            status: ShopStatus::tryFrom((string) ($data['status'] ?? '')) ?? ShopStatus::Active,
            logo: $data['logo'] ?? null,
            removeLogo: (bool) ($data['remove_logo'] ?? false),
        );
    }

    public function toAttributes(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'owner_id' => $this->ownerId,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'status' => $this->status,
        ];
    }
}
