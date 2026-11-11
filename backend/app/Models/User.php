<?php

namespace App\Models;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * Roles and permissions are defined once (guard "web") and shared by the
     * session guard (admin panel) and the JWT guard (API).
     */
    protected $guard_name = 'web';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
        ];
    }

    /** @return HasMany<Shop, $this> */
    public function shops(): HasMany
    {
        return $this->hasMany(Shop::class, 'owner_id');
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'created_by');
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(RoleName::Admin);
    }

    public function isShopOwner(): bool
    {
        return $this->hasRole(RoleName::ShopOwner);
    }

    public function ownsShop(Shop|int $shop): bool
    {
        $shopOwnerId = $shop instanceof Shop
            ? $shop->owner_id
            : Shop::query()->whereKey($shop)->value('owner_id');

        return $shopOwnerId !== null && (int) $shopOwnerId === (int) $this->getKey();
    }

    #[Scope]
    protected function shopOwners(Builder $query): void
    {
        $query->role(RoleName::ShopOwner->value);
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Keep the token payload minimal: only non-sensitive hints for the SPA.
     * Authorization is always re-evaluated server side from the database.
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'roles' => $this->getRoleNames()->all(),
        ];
    }
}
