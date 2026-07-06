# AGENTS.md

> **Single source of truth for all AI coding agents** (Claude Code, Codex, etc.).
> `CLAUDE.md` just imports this file (`@AGENTS.md`) — **always edit HERE**, never edit `CLAUDE.md`. That keeps both in sync automatically.

ShopShop — a **multi-tenant, Shopify-like online shop platform** for the Laos market. Each brand is a tenant (identified by domain). Laravel 13 + Livewire 4, multi-tenancy via `stancl/tenancy`. Payments via BCEL/JDB QR banks, shipping via HAL, SMS/OTP via Telbiz/LTC, realtime via Ably.

> **Full documentation lives in [`docs/`](docs/)** — read it before deep work. Start with [`docs/README.md`](docs/README.md). This file is only the quick-reference.

## Context
- Being **taken over from an outsourced developer**; original repo was on GitLab, this copy is pushed to GitHub (`origin/main`, https://github.com/p-v1shnu/shopshop-optimize).
- Almost all config is currently **hardcoded**; there is **no admin/backoffice yet** — building one is the current focus (see below).
- Only the customer-facing frontend + payment/shipping webhooks exist in this repo.

## Run locally (Docker)
Full setup + rationale: [`docs/07-local-docker.md`](docs/07-local-docker.md).
```bash
docker compose up -d                                   # php-fpm 8.4 + nginx + mysql 8 + redis
docker compose exec app php artisan migrate --seed     # seeds tenants: babybright, muanson, gadzila
docker compose run --rm node sh -c "npm install && npm run build"
docker compose exec app php artisan ...                # run any artisan cmd
```
- Ports (chosen to avoid clashes): web **8899**, mysql **33066**, redis **63799**, vite **5199**.
- **URLs MUST include `:8899`** and use a tenant domain, e.g. `http://gadzila.shopshop.test:8899/shop` (add `*.shopshop.test` → 127.0.0.1 in the hosts file). Without the port → `ERR_CONNECTION_REFUSED`.
- MySQL: user `shopshop` / pass `secret` / db `shopshop`.

## Gotchas / conventions (learned the hard way)
- **Multi-tenant is domain-based** — the app resolves the tenant from the request host. Tenant routes live in `routes/tenant.php`; central/webhook routes in `web.php`/`api.php`.
- `CACHE_STORE` **must be a tag-capable store (redis)** — code uses `Cache::tags()` (HAL + tenancy). `file` store 500s.
- `vendor/` & `node_modules/` are on **named volumes** (not the OneDrive bind mount) for speed — after changing `composer.json`/`package.json`, run install **inside the container**.
- `AppServiceProvider` forces HTTPS only when **not** `local` (so local runs over HTTP).
- php-fpm runs as **root in the dev image only** (bind-mount write perms) — production must use www-data.
- Migrations must run on MySQL 8 / MariaDB 11: **no string defaults on boolean columns** (`->default(true)`, not `'true'`).
- `.env` and RSA keys are gitignored — never commit secrets.

## Current focus — Phase 3: build the backoffice (admin)
**Spec / acceptance criteria:** [`docs/10-prd-backoffice.md`](docs/10-prd-backoffice.md) (lean PRD — the source of "what/done"). Architecture: [`docs/08-backoffice-plan.md`](docs/08-backoffice-plan.md). Deferred/tech-debt: [`docs/09-future-improvements.md`](docs/09-future-improvements.md).
- **One unified admin app, single login** on central domain (`admin.shopshop.la`); role decides scope: `super` (all brands) vs `shop` (own shop only).
- Tech: **custom Livewire 4** (not Filament). Admin UI **English only** in v1.
- **v1 keeps schema changes minimal** — only new additive table is `admins`; de-hardcode into `tenants.data` (JSON); refund uses existing `refunded` status + `shop_order_logs`. Anything needing real schema changes → `docs/09`.
- Phase order: 3.0 foundation (auth/layout/tenant-scope) → 3.1 de-hardcode → 3.2 products+stock → 3.3 orders.
- Image storage: build new upload via S3 driver — local **MinIO**, prod **Cloudflare R2** (`assets.shopshop.la`), env-switchable.

## Working agreement
- **Docs sync:** edit `AGENTS.md` only; `CLAUDE.md` imports it. Keep this file current when conventions/decisions change.
- Do substantial admin work on a **feature branch**, small commits, to ease later merge with the outsourced dev's source.
- Run/verify changes in the Docker stack before saying they work.
