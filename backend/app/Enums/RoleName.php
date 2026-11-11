<?php

namespace App\Enums;

/**
 * System roles. They are seeded, cannot be deleted or renamed from the
 * Roles module, and the application relies on them (e.g. a shop's owner
 * must have the shop_owner role). Admins can create any number of extra
 * custom roles with a hand-picked permission set.
 */
enum RoleName: string
{
    case Admin = 'admin';
    case ShopOwner = 'shop_owner';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::ShopOwner => 'Shop Owner',
            self::Customer => 'Customer',
        };
    }

    /**
     * Default permissions applied when seeding. "admin" is a super-admin:
     * it receives every permission and also bypasses Gate checks.
     *
     * @return list<PermissionName>
     */
    public function defaultPermissions(): array
    {
        return match ($this) {
            self::Admin => PermissionName::cases(),
            self::ShopOwner => [
                PermissionName::CatalogBrowse,
                PermissionName::OwnProductsManage,
                PermissionName::OwnListingsManage,
                PermissionName::OwnOrdersManage,
                PermissionName::OrdersPlace,
            ],
            self::Customer => [
                PermissionName::OrdersPlace,
            ],
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function isSystem(string $name): bool
    {
        return self::tryFrom($name) !== null;
    }
}
