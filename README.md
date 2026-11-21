# Ecommerce System

A production-grade Laravel ecommerce core with a switchable storefront:

- **Blade mode** — server-rendered storefront inside Laravel.
- **Headless mode** — Next.js App Router storefront consuming `/api/v1`.

Both modes share one domain core, one PostgreSQL database, one Redis instance, and
one Filament admin panel.

## Stack

| Layer | Choice |
|---|---|
| PHP | 8.4 |
| Framework | Laravel 13 |
| Admin | Filament 5 |
| Database | PostgreSQL 18 |
| Cache/queue | Redis 8 + Horizon |
| Blade frontend | Blade + Tailwind CSS 4 + Alpine.js + Vite |
| Headless frontend | Next.js 16 + React 19 + TypeScript |
| Edge router | Caddy |
| Tests | Pest, Larastan, Pint |

## Quick start

Requirements: Docker with Compose 2.24+.

```bash
make init
```

`make init` copies the env files, generates `APP_KEY`, builds the images, runs
migrations and seeders, and starts the stack. Open:

- Storefront: <http://localhost:8080>
- Admin: <http://localhost:8080/admin>
- API health: <http://localhost:8080/api/v1/health>
- API version: <http://localhost:8080/api/v1/version>

The seeded admin credentials come from `ADMIN_EMAIL` and `ADMIN_PASSWORD` in
`apps/core/.env` (defaults: `admin@example.com` / `password`). Change them before
any shared or production deployment.

## Commands

```bash
make init                 # env files, key, build, migrate, seed, start
make up MODE=blade        # Blade storefront on :8080
make up MODE=next         # Next storefront on :8080 (edge routes / to Next)
make dev MODE=blade       # bind mounts + dev dependencies
make down                 # stop the stack
make down-v               # stop and delete volumes
make test                 # Pest suite against PostgreSQL/Redis
make lint                 # Pint --test + Larastan
make pint                 # auto-fix PHP style
make fresh                # migrate:fresh --seed
make logs                 # follow container logs
```

## Layout

```
apps/
  core/                   Laravel 13: domain, API, admin, Blade storefront
  storefront-next/        Next.js headless storefront (Phase 5 placeholder)
docker/
  core/                   PHP-FPM + nginx images
  next/                   Next.js image
  edge/                   Caddy routing for both modes
docs/
  PLAN.md                 Scope, architecture, phases
  AGENTS.md               Contributor and agent rules
  adr/                    Architecture decision records
  api/                    Generated OpenAPI specification
compose.yaml              Shared services
compose.*.yaml            Mode and environment overlays
```

## Storefront modes

Set `STOREFRONT_MODE` in `.env` (or use `make up MODE=...`):

| Mode | Root route | Core routes |
|---|---|---|
| `blade` | Laravel Blade | `/`, `/api/*`, `/admin*`, `/livewire/*`, `/storage/*`, `/sanctum/*`, `/horizon*`, `/docs*`, `/up` |
| `headless` | Next.js | `/api/*`, `/admin*`, `/livewire/*`, `/storage/*`, `/sanctum/*`, `/horizon*`, `/docs*`, `/up` |

The edge router is the only component that knows which storefront is active.

## Documentation

- [`docs/PLAN.md`](docs/PLAN.md) — architecture, data model, delivery phases.
- [`docs/AGENTS.md`](docs/AGENTS.md) — coding rules, conventions, definition of done.
- [`docs/adr/`](docs/adr/) — accepted architecture decisions.

## Status

Phases 0–2 are implemented: foundation, catalog/stock-lot pricing with Filament
CRUD and Blade browse, plus guest/auth carts, the shared server-side totals
engine, coupons, the Blade cart, and the cart API. Phase 3 (checkout, orders,
and stock allocation) is next.
