# MultiShop — React storefront & shop-owner panel

Vite + React 19 + TypeScript, Tailwind CSS v4, React Router 7, TanStack Query 5, Zustand, axios.

## Run

```bash
cp .env.example .env        # VITE_API_URL=http://127.0.0.1:8000/api/v1
npm install
npm run dev                 # http://localhost:5173 (the backend CORS allows this origin)
npm run build               # type-check + production build to dist/
```

The Laravel API must be running (`cd ../backend && php artisan serve`).

Demo accounts (password `password`): `customer@multishop.com`, `rahim@multishop.com` (shop owner),
`admin@multishop.com` (admin, uses the Blade panel at `/admin`).

## Structure

```
src/
  api/          axios client (JWT + one-shot refresh queue, Accept-Language), JSON:API helpers, typed requests + react-query hooks per resource
  app/          router (lazy routes) and session watcher
  components/   ui/ design-system primitives, layout/ store header/footer
  features/     storefront, cart, checkout, auth, account, orders, owner
  hooks/        focus trap, form errors (422 meta.field → fields), URL-synced filters, debounce
  lib/          formatting (৳ 1,250.00), category tree, FormData bracket encoder, i18n dict
  stores/       auth + locale (persisted)
  types/        API envelope + domain models
```
