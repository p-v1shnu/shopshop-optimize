# 05 — Infrastructure, Workers & Deployment

## 1. Service Providers ([`app/Providers/`](../app/Providers/))

ลงทะเบียนใน [`bootstrap/providers.php`](../bootstrap/providers.php): `AppServiceProvider`, `AuthServiceProvider`, `TenancyServiceProvider`

| Provider | หน้าที่ |
|----------|---------|
| `AppServiceProvider` | singleton `Setting`, บังคับ HTTPS, default string length 255, **ปิด destructive migration** เว้นแต่ `DB_ENABLE_MIGRATION=true` |
| `AuthServiceProvider` | gate `role-user` (`$user->role === 'user'`) |
| `TenancyServiceProvider` | ลงทะเบียน tenancy events/listeners ทั้งหมด, map `routes/tenant.php`, prepend tenancy middleware, ตั้ง Livewire update route ให้ผ่าน tenancy |

Bootstrap หลัก: [`bootstrap/app.php`](../bootstrap/app.php)
- โหลด routes: web/api/console/channels, health check `/up`
- prepend middleware `AddRequestContext`
- exception handling: `TenantCouldNotBeIdentifiedOnDomainException` → redirect main domain; `HttpException` → JSON (api) หรือ error view (web); เชื่อม Flare

## 2. Console Commands & Scheduler

Commands: [`app/Console/Commands/`](../app/Console/Commands/) — Schedule: [`routes/console.php`](../routes/console.php)

| Command | ตารางเวลา | หน้าที่ |
|---------|-----------|---------|
| `SendInvoiceWebhook` | ทุก 30 วินาที | ส่ง webhook ออเดอร์ที่จ่ายแล้วไป `tenant.order_invoice_webhook_url` (RSA-SHA256 sign) ครั้งละ 20 ออเดอร์, mark `notified_invoice_api_at` |
| `CleanUnPaidOrders` | ทุก 30 วินาที | ออเดอร์ pending ที่หมดอายุ → mark `expired`, **คืนสต็อก + คืนคูปอง** (pessimistic lock) |
| `ReconcileOnepay` | (เรียกเอง/ตั้งเพิ่มได้) | เช็คสถานะจ่ายเงินกับ BCEL OnePay API เผื่อ webhook พลาด |
| `SubscribeHALWebhook` | (เรียกเอง) | สมัคร webhook กับ HAL |
| `UpdateOrderCode` | (เรียกเอง) | เติม order_code ให้ออเดอร์ที่ยังไม่มี |
| `ClearHalCache` | (เรียกเอง) | flush cache tag `HAL` |
| `ClearCartSession` | (เรียกเอง) | ลบ `cartProducts` ออกจากไฟล์ session |

> Scheduler 2 ตัวแรกตั้ง `->everyThirtySeconds()` แบบ `runInMaintenanceMode` + single server → ต้องมี **cron** เรียก `php artisan schedule:run` ทุกนาที (Laravel scheduler มาตรฐาน)

## 3. Node.js Workers ([`workers/`](../workers/))

Process แยกจาก PHP ใช้ **PubNub** ฟัง notification การจ่ายเงินจากธนาคาร แล้วยิง webhook กลับเข้า Laravel

| ไฟล์ | หน้าที่ |
|------|---------|
| [`bcel.cjs`](../workers/bcel.cjs) | subscribe PubNub BCEL → POST `/api/webhooks/bcel` |
| [`jdb.cjs`](../workers/jdb.cjs) | subscribe PubNub JDB → POST `/api/webhooks/jdb` (ปิดใน ecosystem ปัจจุบัน) |
| [`logger.cjs`](../workers/logger.cjs) | Pino logger (daily rotate, retention 365 วัน, max 1GB, redact header sensitive) |

Log: `workers/logs/{worker}-{YYYYMMDD}.log` (+ symlink `today.log`)

**Env สำหรับ worker:** `NODE_ENV`, `LOG_LEVEL`, `PAYMENT_UUID_PREFIX` + config BCEL/JDB ทั้งหมด

## 4. PM2

- Prod: [`ecosystem.config.cjs`](../ecosystem.config.cjs) — active: `shopshop-bcel-worker` (1 instance, autorestart, cron restart เที่ยงคืน `0 0 * * *`, max memory 1GB); `shopshop-jdb-worker` comment ไว้
- UAT: [`ecosystem-uat.config.cjs`](../ecosystem-uat.config.cjs) — เหมือนกัน ชื่อ `shopshop-uat-bcel-worker`

```bash
pm2 start ecosystem.config.cjs       # prod
pm2 start ecosystem-uat.config.cjs   # uat
pm2 logs shopshop-bcel-worker
```

## 5. Build (Vite)

- [`vite.config.js`](../vite.config.js): Vue 2 + Laravel Vite plugin + legacy (ES5 fallback), entry = `resources/css/app.scss`, `resources/js/app.js`, `resources/js/echo.js`, output → `public/build_prod` (alias `vue` → `vue/dist/vue.esm.js`)
- [`package.json`](../package.json) scripts:
  - `npm run dev` → `vite`
  - `npm run build` → build แล้วย้าย `build_prod` → `build`
- Tailwind: [`tailwind.config.js`](../tailwind.config.js), PostCSS: [`postcss.config.js`](../postcss.config.js)
- Node version (Volta): **22.15.0**

## 6. Deploy

[`deploy.sh`](../deploy.sh) — สคริปต์เบามาก (pause scheduler ระหว่าง deploy; คำสั่ง `optimize` ถูก comment ไว้)
```bash
php artisan schedule:interrupt
# php artisan optimize:clear
# php artisan optimize
```

ขั้นตอน deploy ทั่วไปที่ควรทำ (แนะนำ):
```bash
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force        # ต้องตั้ง DB_ENABLE_MIGRATION=true (ดู AppServiceProvider)
php artisan optimize
pm2 reload ecosystem.config.cjs
```

## 7. Environment (.env) — กลุ่มสำคัญ

ดูตัวอย่างเต็มที่ [`.env.example`](../.env.example) — timezone ดีฟอลต์ `Asia/Vientiane`

| กลุ่ม | ตัวแปรหลัก |
|-------|-----------|
| App/DB | `APP_URL` (กำหนด central domain), `DB_*`, `CACHE_STORE`, `QUEUE_CONNECTION`, `SESSION_*` |
| Realtime | `ABLY_KEY`, `BROADCAST_CONNECTION` |
| BCEL | `BCEL_QR_*` |
| JDB | `JDB_QR_*` |
| HAL | `HAL_*` |
| SMS/OTP | `TELBIZ_*`, `LTC_SMS_*` |
| Signing | `PUBLIC_KEY_NAME`, `PRIVATE_KEY_NAME` |
| Cloudflare | `CLOUDFLARE_API_TOKEN`, `CLOUDFLARE_ZONE_ID`, `FLUSH_CACHE_SECRET` |
| อื่น ๆ | `PAYMENT_UUID_PREFIX`, `SHOP_CAMPAIGN_CODE`, `GOOGLE_MAP_API_KEY` |

Custom config รวมที่ [`config/custom.php`](../config/custom.php) เช่น `db_enable_migration`, `main_domain` (`shopshop.test`), `enable_search`, `shop_free_shipping`, `shop_discount_hal`

## 8. Utilities & Helpers

[`app/Utils/`](../app/Utils/): `AppUtil` (asset resolve), `OtpUtil`, `ShopUtil` (cart/stock/campaign), `HalUtil`, `BCELUtil`, `JDBUtil`, `WebhookUtil` (RSA sign), `FormUtil` (dropdown จังหวัด/อำเภอ/วันเกิด/เพศ ภาษาลาว), `RewardUtil`

[`app/helpers.php`](../app/helpers.php): `setting($key, $default)` — อ่านจาก singleton `Setting`
