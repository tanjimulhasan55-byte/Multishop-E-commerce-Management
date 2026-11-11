# Backend (Laravel 13)

See the [root README](../README.md) for setup, architecture and demo accounts.

Quick start:

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate && php artisan jwt:secret --force
# set DB_* in .env, create the database, then:
php artisan migrate --seed && php artisan storage:link && npm run build
composer run dev   # server + queue worker + vite
```
