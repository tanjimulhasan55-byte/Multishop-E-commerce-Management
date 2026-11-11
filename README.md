# Multi-shop E-commerce Management

Laravel 13 (API + Admin panel) · React (Storefront + Shop-owner panel) · MySQL

<p align="center">
  <img src="docs/screenshots/02-admin-dashboard.jpg" width="49%" alt="Admin dashboard">
  <img src="docs/screenshots/15-store-product-variants.jpg" width="49%" alt="Storefront product page with variant picker">
</p>

> 📸 See the full [screenshot walkthrough](#-screenshots--walkthrough) at the end of this file.

```
backend/    Laravel 13 — API + admin panel
frontend/   React — storefront + shop-owner panel
```

## Requirements

- PHP 8.3+ (extensions: `pdo_mysql`, `mbstring`, `gd`, `fileinfo`, `intl`, `zip`)
- Composer 2
- MySQL 8+
- Node.js 20+

## Installation

### 1. Backend

```bash
cd backend
composer install
npm install

cp .env.example .env          # Windows PowerShell: Copy-Item .env.example .env
php artisan key:generate
php artisan jwt:secret --force
```

Create a MySQL database:

```sql
CREATE DATABASE multishop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Set your MySQL username/password in `backend/.env`:

```dotenv
DB_DATABASE=multishop
DB_USERNAME=root
DB_PASSWORD=your-password
```

Create the tables and load all the demo data, then run:

```bash
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve             # http://127.0.0.1:8000
```

In a second terminal (for image thumbnails):

```bash
php artisan queue:work
```

### 2. Frontend

```bash
cd frontend
npm install
cp .env.example .env          # Windows PowerShell: Copy-Item .env.example .env
npm run dev                   # http://localhost:5173
```

## URLs

| What | URL |
|---|---|
| Admin panel | http://127.0.0.1:8000/admin |
| Storefront + shop-owner panel | http://localhost:5173 |
| API docs | http://127.0.0.1:8000/docs/api |

## Login credentials

Password for every account: **`password`**

| Role | Email | Login at |
|---|---|---|
| Admin (full access) | `admin@multishop.com` | Admin panel |
| Catalog Manager (custom role, limited access) | `manager@multishop.com` | Admin panel |
| Shop owner — Dhaka Gadget House | `rahim@multishop.com` | Storefront → Owner panel |
| Shop owner — Chattogram Fashion Hub | `karim@multishop.com` | Storefront → Owner panel |
| Shop owner — Sylhet Fresh Mart | `nusrat@multishop.com` | Storefront → Owner panel |
| Customer | `customer@multishop.com` | Storefront |

## Demo data (created by `php artisan migrate --seed`)

- **Roles & permissions:** Admin, Shop Owner, Customer + a custom "Catalog Manager" role
- **Categories (nested):** Electronics › Audio / Wearables / Accessories, Fashion › Men › T-Shirts / Panjabi, Women › Sarees, Footwear, …
- **Attributes:** Color (Black, White, Navy, Maroon), Size (S, M, L, XL, XXL), Shoe Size (EU 40–43)
- **Products with variants and their own SKUs**, e.g. Premium Cotton T-Shirt → `TSHIRT-01-BLK-S` … `TSHIRT-01-NVY-XL`
- **Shops** with their own price and stock for each variant, plus sample orders
- **Hidden from customers on purpose:** Portable Projector and Bamboo Cutting Board (not listed by any shop), Aloe Vera Gel (draft), and Rajshahi Book Corner (inactive shop)

To reset everything: `php artisan migrate:fresh --seed`

## Tests

```bash
cd backend
php artisan test
```

24 feature tests (119 assertions) run on an in-memory SQLite database, so they need no setup.

---

## Features

### 🛠️ Admin panel (`/admin`)

Built with Blade, Laravel Breeze, Tailwind CSS and Yajra DataTables.

**Dashboard**
- Revenue, order, shop and product totals.
- Sales chart for the last 14 days and an "orders by status" chart.
- Latest orders and a low-stock alert list.

**Shops (full CRUD)**
- Create, edit and delete shops, with a name and description in English and Bangla, contact details and a logo.
- Assign a shop owner to each shop.
- Set a shop to Active or Inactive. Hiding a shop instantly hides all of its products from customers.

**Products (full CRUD)**
- Manage the shared catalog: name and description (English / Bangla), category, base price, SKU, status (Draft / Active / Archived) and thumbnail.
- See which shops sell each product, and filter for products that no shop lists yet.

**Product variants & SKUs**
- Pick option values (e.g. Color: Black, White × Size: S, M, L, XL) and every combination is created automatically.
- Each variant gets its own SKU, e.g. `TSHIRT-01-BLK-XL`.
- Edit a variant's SKU or price, or delete it. Running the generator again keeps existing variants and only adds new ones.

**Categories**
- Unlimited nested categories (e.g. Fashion › Men › T-Shirts), up to 5 levels deep.
- A category can't be moved under itself or one of its own sub-categories.

**Attributes**
- Create variant options such as Size or Color.
- Each value has a short SKU code, and colours have a colour swatch.

**Shop owners & users**
- Create and manage shop-owner accounts (and any other account).
- Suspend or reactivate users.
- The last administrator can never be removed.

**Roles & permissions**
- Create custom roles (e.g. "Support Agent") and tick exactly which permissions each one gets, grouped by module.
- Users with a limited role only see the menus and actions they're allowed to use.
- Example included: `manager@multishop.com` (Catalog Manager role).

**Listings & orders**
- See which shop sells which product, at what price and stock.
- View every order and move it through Pending → Processing → Shipped → Delivered, or cancel it.

**Usability**
- Fast server-side tables with search, sorting, filters and pagination.
- Confirmation dialogs before deleting, toast messages, and a responsive layout.

### 🏪 Shop-owner panel (React app → Owner panel)

- **Dashboard:** number of shops, listings, low-stock items, orders and revenue.
- **My shops:** switch between your shops if you own more than one.
- **Shared catalog:**
  - Browse every product created by the admin or by other shop owners.
  - Search, filter by category, and show only products that aren't in your shop yet.
  - Add a product to your shop in one step.
- **My products:** create your own products, which immediately appear in the shared catalog for every other shop. You can also generate variants for them.
- **Listings (your shop's products):**
  - Set your own **price, compare-at (discount) price and stock for each variant**.
  - Override the **title and description** in English and Bangla.
  - Upload your own **thumbnail**.
  - Turn a listing on or off, filter low-stock items, and use "apply to all variants" for quick edits.
- **Orders:** see orders placed with your shops and update their status.
- **Security:** an owner can only see and change their own shops, listings, products and orders.

### 🛍️ Customer storefront (React app)

- **Only products assigned to an active shop are visible.**
  - A product no shop has listed is never shown.
  - Neither is a draft product, an inactive listing or a product from an inactive shop.
- **Shop-specific product pages:** each shop's own price, title, description and thumbnail. The same product can be sold by different shops at different prices.
- **Browse:** home page, all products, shops, and a page for each shop.
- **Filters:** nested category tree, search, price range, in stock only, and sorting by price or newest.
- **Variant picker:**
  - Colour swatches and size buttons.
  - Sold-out combinations are disabled.
  - The selected variant's price, stock and SKU are shown.
- **Cart** grouped by shop, with a slide-out cart drawer.
- **Checkout:**
  - Cash on Delivery (SSLCommerz online payment is supported when keys are configured).
  - Prices are always calculated on the server.
  - Stock can't be oversold, even with simultaneous orders.
- **Account:** register, log in, edit your profile, change your password, and see your order history with an order status timeline.
- **English / বাংলা** language switch.
- **Responsive** on mobile, tablet and desktop.

### 🔐 Security

- Admin panel login (Laravel Breeze) only lets in active staff accounts. Public registration for the admin panel is disabled.
- The API uses **JWT** tokens:
  - Short-lived access tokens with refresh.
  - Logging out invalidates the token.
  - Suspended users are blocked immediately.
- Every action is checked server-side by **policies and permissions** (spatie/laravel-permission).
- All input is validated in dedicated **Form Requests**, and image uploads are restricted to JPG, PNG or WebP up to 2 MB.
- Rate limiting on login, checkout and uploads.
- Security headers, and CORS restricted to the frontend.

### ⚙️ Architecture & database

**Clean, layered code**
- Every request goes Route → thin controller → Form Request (validation + authorization) → readonly **DTO** → **Action** (one class per use case).
- Responses are JSON:API resources.
- Folders follow `Actions/`, `DTOs/`, `Enums/`, `Http/Controllers/Api/V1`, `Http/Requests/Api/V1`, `Http/Resources/Api/V1`, `Contracts/`, `Services/`.

**Versioned REST API** (`/api/v1`)
- Filtering, sorting, includes and pagination (spatie/laravel-query-builder).
- Interactive API documentation at `/docs/api` (Scramble).

**Database design**
- Shared `products` catalog, plus per-shop `shop_products` listings and `shop_product_variants` with each shop's price and stock.
- Foreign keys everywhere, plus indexes matched to the actual queries, including MySQL full-text search.
- Order items keep a snapshot of the name, SKU and price, so order history never changes.

**Images**
- Thumbnail, medium and WebP versions are generated automatically (spatie/laravel-medialibrary).

**Bilingual content**
- English and Bangla for product, shop and category names and descriptions (spatie/laravel-translatable).
- Clean URL slugs (spatie/laravel-sluggable).

---

## 📸 Screenshots & walkthrough

The overall process is:

1. The **admin** sets up the catalog, shops and shop-owner accounts.
2. **Shop owners** pick products from the shared catalog (or create their own) and set their own prices.
3. **Customers** only see what shops are actually selling.

### Step 1 — Admin sets up the marketplace (`/admin`)

<table>
  <tr>
    <td width="50%"><img src="docs/screenshots/01-admin-login.jpg" alt="Admin login"><br><b>Admin login</b> — only staff accounts can sign in.</td>
    <td width="50%"><img src="docs/screenshots/02-admin-dashboard.jpg" alt="Dashboard"><br><b>Dashboard</b> — revenue, orders, shops, products, sales chart.</td>
  </tr>
  <tr>
    <td><img src="docs/screenshots/03-admin-shops.jpg" alt="Shops"><br><b>Shops</b> — list, search, filter by status or owner.</td>
    <td><img src="docs/screenshots/04-admin-shop-form.jpg" alt="Create shop"><br><b>Create / edit shop</b> — English & Bangla name, owner, status, logo.</td>
  </tr>
  <tr>
    <td><img src="docs/screenshots/05-admin-products.jpg" alt="Products"><br><b>Shared product catalog</b> — variants, base price, which shops sell it, "Not listed" badge.</td>
    <td><img src="docs/screenshots/06-admin-product-variants.jpg" alt="Product variants"><br><b>Product variants</b> — pick Color × Size and every combination gets its own SKU.</td>
  </tr>
  <tr>
    <td><img src="docs/screenshots/07-admin-categories.jpg" alt="Categories"><br><b>Nested categories</b> — Fashion › Men › T-Shirts.</td>
    <td><img src="docs/screenshots/08-admin-attribute-values.jpg" alt="Attribute values"><br><b>Attributes & values</b> — Size: S, M, L, XL… with SKU codes.</td>
  </tr>
  <tr>
    <td><img src="docs/screenshots/09-admin-shop-owners.jpg" alt="Shop owners"><br><b>Shop owner accounts</b> — create, edit, suspend.</td>
    <td><img src="docs/screenshots/10-admin-role-permissions.jpg" alt="Roles and permissions"><br><b>Roles & permissions</b> — choose exactly what each role can do.</td>
  </tr>
  <tr>
    <td colspan="2"><img src="docs/screenshots/11-admin-orders.jpg" alt="Orders" width="50%"><br><b>Orders</b> — every order across all shops, with status and payment.</td>
  </tr>
</table>

### Step 2 — Shop owners list products in their shop (`http://localhost:5173/owner`)

<table>
  <tr>
    <td width="50%"><img src="docs/screenshots/19-owner-dashboard.jpg" alt="Owner dashboard"><br><b>Owner dashboard</b> — their shops, listings, low stock, orders.</td>
    <td width="50%"><img src="docs/screenshots/20-owner-shared-catalog.jpg" alt="Shared catalog"><br><b>Shared catalog</b> — products from the admin and other shop owners.</td>
  </tr>
  <tr>
    <td><img src="docs/screenshots/21-owner-add-to-shop.jpg" alt="Add to shop"><br><b>Add to shop</b> — set their own price, stock, title and image.</td>
    <td><img src="docs/screenshots/22-owner-listings.jpg" alt="Listings"><br><b>Their listings</b> — price, stock, variants, on/off switch.</td>
  </tr>
  <tr>
    <td><img src="docs/screenshots/23-owner-edit-listing-variant-prices.jpg" alt="Edit listing"><br><b>Edit listing</b> — different price and stock for every variant.</td>
    <td><img src="docs/screenshots/24-owner-my-product-variants.jpg" alt="My products"><br><b>My products</b> — owners create their own products and variants, which join the shared catalog.</td>
  </tr>
  <tr>
    <td colspan="2"><img src="docs/screenshots/25-owner-orders.jpg" alt="Owner orders" width="50%"><br><b>Orders</b> — orders for their shops; move them Processing → Shipped → Delivered.</td>
  </tr>
</table>

### Step 3 — Customers shop (`http://localhost:5173`)

<table>
  <tr>
    <td width="50%"><img src="docs/screenshots/12-store-home.jpg" alt="Home"><br><b>Home</b> — categories, shops and latest products.</td>
    <td width="50%"><img src="docs/screenshots/13-store-products.jpg" alt="Products"><br><b>All products</b> — only products assigned to a shop, with filters.</td>
  </tr>
  <tr>
    <td><img src="docs/screenshots/14-store-shops.jpg" alt="Shops"><br><b>Shops</b> — browse each shop's own catalog.</td>
    <td><img src="docs/screenshots/15-store-product-variants.jpg" alt="Product page"><br><b>Product page</b> — the shop's price and image; colour & size picker shows the exact SKU.</td>
  </tr>
  <tr>
    <td><img src="docs/screenshots/16-store-cart-drawer.jpg" alt="Cart"><br><b>Cart</b> — grouped by shop.</td>
    <td><img src="docs/screenshots/17-store-checkout.jpg" alt="Checkout"><br><b>Checkout</b> — delivery details and Cash on Delivery.</td>
  </tr>
  <tr>
    <td><img src="docs/screenshots/18-store-order-placed.jpg" alt="Order placed"><br><b>Order placed</b> — status timeline, items with variant and SKU.</td>
    <td align="center"><img src="docs/screenshots/26-mobile-product.jpg" alt="Mobile" width="45%"><br><b>Mobile friendly</b> — works on phones too.</td>
  </tr>
</table>
