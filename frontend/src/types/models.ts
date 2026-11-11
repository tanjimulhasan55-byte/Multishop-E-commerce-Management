import type { Flat } from './api';

/* ---------- Shared value objects ---------- */

/** Decimals arrive as strings, e.g. "1250.00". */
export type Money = string;
export type Locale = 'en' | 'bn';
export type Translations = Partial<Record<Locale, string>>;

/** Laravel serialises an empty translation map as `[]`. */
export type MaybeTranslations = Translations | unknown[];

export interface ImageSet {
  original: string;
  thumb: string;
  medium: string;
  webp: string;
}

export interface OptionValue {
  id: number;
  value: string;
  color_hex: string | null;
}

export type OptionType = 'select' | 'color';

export interface ProductOption {
  attribute_id: number;
  name: string;
  type: OptionType;
  sort_order?: number;
  values: OptionValue[];
}

/* ---------- Users / auth ---------- */

export interface UserAttributes {
  name: string;
  email: string;
  phone: string | null;
  status: string;
  roles: string[];
  permissions: string[];
  last_login_at?: string | null;
  created_at?: string;
}
export type User = Flat<UserAttributes>;

export interface TokenMeta {
  access_token: string;
  token_type: string;
  expires_in: number;
}

/* ---------- Storefront ---------- */

export type PaymentMethod = 'cod' | 'sslcommerz';

export interface PaymentMethodOption {
  value: PaymentMethod;
  label: string;
}

export interface StorefrontSettings {
  currency: string;
  locales: Locale[];
  payment_methods: PaymentMethodOption[];
}

export interface CategoryAttributes {
  name: string;
  slug: string;
  parent_id: number | null;
  depth: number;
  path: string;
  is_active: boolean;
  sort_order: number;
}
export type Category = Flat<CategoryAttributes>;
export interface CategoryNode extends Category {
  children: CategoryNode[];
}

export interface ShopAttributes {
  name: string;
  slug: string;
  description: string | null;
  email: string | null;
  phone: string | null;
  address: string | null;
  status: string;
  logo: ImageSet | null;
  listings_count?: number;
  owner_id?: number;
  created_at?: string;
}
export type Shop = Flat<ShopAttributes>;

export interface ListingVariant {
  /** Shop-variant id — what checkout expects as `variant_id`. */
  id: number;
  /** Product-variant id — what listing writes expect as `variant_id`. */
  variant_id: number;
  sku: string;
  label: string | null;
  option_value_ids: number[];
  price: Money;
  compare_at_price: Money | null;
  stock: number;
  in_stock: boolean;
  is_active: boolean;
}

export interface ListingAttributes {
  title: string;
  description: string | null;
  price: Money;
  compare_at_price: Money | null;
  stock: number;
  in_stock: boolean;
  is_active: boolean;
  images: ImageSet | null;
  shop_id: number;
  product_id: number;
  sku: string;
  category: string | null;
  has_options: boolean;
  options: ProductOption[];
  variants: ListingVariant[];
  created_at?: string;
  /* owner context only */
  overrides?: { title: MaybeTranslations; description: MaybeTranslations };
  has_own_thumbnail?: boolean;
}
export type Listing = Flat<ListingAttributes> & { shop?: Shop };

/* ---------- Orders ---------- */

export type OrderStatus = 'pending' | 'processing' | 'shipped' | 'delivered' | 'cancelled';
export type PaymentStatus = 'unpaid' | 'pending' | 'paid' | 'failed' | 'cancelled' | 'refunded';

export interface OrderItem {
  listing_id: number;
  variant_id: number;
  product_name: string;
  variant_label: string | null;
  sku: string;
  unit_price: Money;
  quantity: number;
  line_total: Money;
}

export interface OrderAttributes {
  number: string;
  status: OrderStatus;
  payment_status: PaymentStatus;
  payment_method: PaymentMethod;
  subtotal: Money;
  shipping_total: Money;
  total: Money;
  currency: string;
  customer_name: string;
  customer_phone: string;
  shipping_address: string;
  notes: string | null;
  shop_id: number;
  shop_name: string;
  next_statuses: OrderStatus[];
  items: OrderItem[];
  placed_at: string;
}
export type Order = Flat<OrderAttributes>;

export interface PlaceOrderPayload {
  shop_id: number;
  items: { variant_id: number; quantity: number }[];
  customer_name: string;
  customer_phone: string;
  shipping_address: string;
  notes?: string;
  payment_method: PaymentMethod;
}

/* ---------- Owner panel ---------- */

export interface DashboardStats {
  shops: number;
  listings: number;
  active_listings: number;
  low_stock: number;
  my_products: number;
  orders: number;
  pending_orders: number;
  revenue: Money;
}

export type ProductStatus = 'draft' | 'active' | 'archived';

export interface ProductVariant {
  id: number;
  sku: string;
  label: string | null;
  is_default: boolean;
  base_price: Money;
  option_value_ids: number[];
}

export interface ProductAttributes {
  name: string;
  slug: string;
  sku: string;
  description: string | null;
  base_price: Money;
  status: ProductStatus;
  category_id: number | null;
  category_path?: string | null;
  created_by: number | null;
  is_mine: boolean;
  can: { update: boolean; delete: boolean };
  thumbnail: ImageSet | null;
  listed_in_shop?: boolean;
  shops_count: number;
  options: ProductOption[];
  variants: ProductVariant[];
  translations: { name: MaybeTranslations; description: MaybeTranslations };
  created_at?: string;
}
export type Product = Flat<ProductAttributes>;

export interface AttributeValue {
  id: number;
  value: string;
  code: string;
  color_hex: string | null;
  sort_order?: number;
}

export interface CatalogAttributeAttributes {
  name: string;
  slug: string;
  type: OptionType;
  sort_order?: number;
  values: AttributeValue[];
}
export type CatalogAttribute = Flat<CatalogAttributeAttributes>;
