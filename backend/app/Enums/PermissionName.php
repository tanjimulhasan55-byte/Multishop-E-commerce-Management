<?php

namespace App\Enums;

/**
 * Every permission the application checks. Permissions are code-defined
 * (the code is what enforces them) and synced to the database by
 * RolesAndPermissionsSeeder; *roles* are data that admins manage in the
 * Roles & Permissions module.
 */
enum PermissionName: string
{
    // Admin panel
    case DashboardView = 'dashboard.view';

    case ShopsView = 'shops.view';
    case ShopsCreate = 'shops.create';
    case ShopsUpdate = 'shops.update';
    case ShopsDelete = 'shops.delete';

    case ProductsView = 'products.view';
    case ProductsCreate = 'products.create';
    case ProductsUpdate = 'products.update';
    case ProductsDelete = 'products.delete';

    case CategoriesView = 'categories.view';
    case CategoriesCreate = 'categories.create';
    case CategoriesUpdate = 'categories.update';
    case CategoriesDelete = 'categories.delete';

    case AttributesView = 'attributes.view';
    case AttributesCreate = 'attributes.create';
    case AttributesUpdate = 'attributes.update';
    case AttributesDelete = 'attributes.delete';

    case ListingsView = 'listings.view';
    case ListingsDelete = 'listings.delete';

    case OrdersView = 'orders.view';
    case OrdersUpdate = 'orders.update';

    case UsersView = 'users.view';
    case UsersCreate = 'users.create';
    case UsersUpdate = 'users.update';
    case UsersDelete = 'users.delete';

    case RolesView = 'roles.view';
    case RolesCreate = 'roles.create';
    case RolesUpdate = 'roles.update';
    case RolesDelete = 'roles.delete';

    // Shop-owner panel (React SPA)
    case CatalogBrowse = 'catalog.browse';
    case OwnProductsManage = 'own-products.manage';
    case OwnListingsManage = 'own-listings.manage';
    case OwnOrdersManage = 'own-orders.manage';

    // Customer
    case OrdersPlace = 'orders.place';

    public function group(): string
    {
        return match (true) {
            str_starts_with($this->value, 'own-'), $this === self::CatalogBrowse => 'Shop Owner Panel',
            $this === self::OrdersPlace => 'Customer',
            default => ucfirst(explode('.', $this->value)[0]),
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::DashboardView => 'View dashboard',
            self::CatalogBrowse => 'Browse shared catalog',
            self::OwnProductsManage => 'Create & edit own catalog products',
            self::OwnListingsManage => 'Manage own shop listings (price, details, thumbnail)',
            self::OwnOrdersManage => 'Manage own shop orders',
            self::OrdersPlace => 'Place orders',
            default => ucfirst(explode('.', $this->value)[1]).' '.str_replace('-', ' ', explode('.', $this->value)[0]),
        };
    }

    /** @return array<string, list<self>> group => permissions */
    public static function grouped(): array
    {
        return collect(self::cases())->groupBy(fn (self $p) => $p->group())->map->all()->all();
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
