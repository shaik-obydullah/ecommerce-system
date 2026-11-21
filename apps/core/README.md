# Ecommerce Core

Laravel 13 application containing the domain core, JSON API, Filament admin, and the
Blade storefront.

## Local development

From the repository root:

```bash
make init
make dev
make test
make lint
```

The core runs in Docker; the host does not need PHP or Composer.

## Entry points

| Path | Purpose |
|---|---|
| `/` | Blade storefront when `STOREFRONT_MODE=blade` |
| `/admin` | Filament admin panel (separate `admin` guard) |
| `/api/v1/health` | Database and cache health |
| `/api/v1/version` | Build and runtime metadata |
| `/up` | Liveness probe used by Docker |

## Domain layout

```
app/Core/Support/Money       Money and Currency value objects
app/Core/Support/Settings    Cached settings access
app/Http/Api/V1              JSON API controllers
app/Http/Storefront          Blade controllers
app/Filament                 Admin resources and pages
```

Domain modules (Catalog, Inventory, Cart, Checkout, Orders, Payments, etc.) are added
in later phases. Business rules belong in `app/Core`, not in controllers or views.

## Configuration

`config/ecommerce.php` holds storefront mode, base/default currencies, supported
currencies, currency metadata, and API limits.

Environment variables are documented in `.env.example`. Never commit `.env`.

## Quality

```bash
docker compose exec core php artisan test
docker compose exec core ./vendor/bin/pint --test
docker compose exec core ./vendor/bin/phpstan analyse --memory-limit=512M
```

Tests run against PostgreSQL and Redis, not SQLite.
