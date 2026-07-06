# 01 — สถาปัตยกรรมระบบ (Architecture)

## 1. ภาพรวม Multi-Tenancy

ระบบใช้แพ็กเกจ **stancl/tenancy 3.10** แยก tenant ตาม **domain** (single-database-per-tenant identification by domain)

- **Tenant** = 1 brand/ร้านค้า → มี id เป็น string (ไม่ auto-increment) เช่น `gadzila`
- แต่ละ tenant map กับ 1 หรือหลาย **domain** (ตาราง `domains`)
- Config: [`config/tenancy.php`](../config/tenancy.php)

### การ identify tenant
เมื่อ request เข้ามาที่ tenant domain → middleware `InitializeTenancyByDomain` ดึง tenant จาก host ของ URL → bootstrap tenancy context

### Central domain vs Tenant domain
| | Central domain | Tenant domain |
|---|---|---|
| ตัวอย่าง | `shopshop.la` / `shopshop.test` | `gadzila.shopshop.la` |
| Routes | [`routes/web.php`](../routes/web.php), [`routes/api.php`](../routes/api.php) | [`routes/tenant.php`](../routes/tenant.php) |
| หน้าที่ | landing page, **webhook** (payment/shipping), flush cache, redirect invoice | หน้าร้านทั้งหมด (shop, cart, checkout, orders, profile) |

> `central_domains` คำนวณจาก host ของ `config('app.url')` — main domain สำรองอยู่ที่ `config/custom.php` (`shopshop.test`)

### Tenancy bootstrappers (config/tenancy.php)
- `CacheTenancyBootstrapper` — cache แยกตาม tenant (tag base `shopshop_tenant`)
- `QueueTenancyBootstrapper` — queued job แยกตาม tenant
- `RedisTenancyBootstrapper` — prefix Redis key (`shopshop_tenant`)

### Features เปิดใช้
- `UniversalRoutes` — บาง route เข้าได้ทั้ง central และ tenant
- `TenantConfig` — override config รายร้าน
- `ViteBundler` — asset ต่อ tenant

### Tenant model ([`app/Models/Tenant.php`](../app/Models/Tenant.php))
เก็บ config รายร้านจำนวนมาก เช่น: `status`, `enable_shop`, `enable_coupon`, `order_invoice_webhook_url`, `site_logo_url`, `facebook_*`, `homepage_banners`, `popup_banners`, `head_html`, `google_tag_manager_id`, `google_analytics_id`, `maintenance_mode`, `allow_province_ids`, `shipping_channels`, `latitude`/`longitude` (สำหรับร้านแบบ pickup) เป็นต้น
- ความสัมพันธ์: `domains()` (HasMany)
- Traits: `HasDatabase`, `HasDomains`, `MaintenanceMode`, `SerializesDatesToAppTimezone`

> **หมายเหตุสำคัญ:** ถึงจะใช้ stancl/tenancy แต่ในโค้ดชุดนี้ **ยังไม่มี tenant migration แยก** (`database/migrations/tenant` ว่าง) — ทุกตารางอยู่ในฐานเดียว โดยตาราง shop ต่าง ๆ ใช้คอลัมน์ `tenant_id` แยกข้อมูลรายร้าน (single-database, tenant-scoped by `tenant_id`) ดูรายละเอียดใน [02-database.md](02-database.md)

## 2. Request lifecycle (tenant domain)

```
Request → tenant domain
  └─ middleware: web
       └─ InitializeTenancyByDomain      (ระบุ tenant จาก domain)
            └─ PreventAccessFromCentralDomains
                 └─ CheckTenantStatus     (ถ้า tenant inactive → redirect main domain)
                      └─ CheckTenantForMaintenanceMode
                           └─ [route middleware] → Livewire component
```

Global middleware เพิ่มเติม: `AddRequestContext` (prepend) — ใส่ `request_id` (UUID) + `user_id` ลง Laravel Context สำหรับ logging

## 3. Routing map

### Tenant routes ([`routes/tenant.php`](../routes/tenant.php))
| Path | Component | สิทธิ์ |
|------|-----------|--------|
| `/` | → redirect `/shop` | - |
| `/shop/search` | `ShopSearchPage` | public |
| `/shop` | `ShopProductsPage` | ต้องมี profile ครบ (`CheckProfile`) |
| `/shop/products/{productId}` | `ShopProductDetailPage` | `CheckProfile` |
| `/profile`, `/edit-profile` | `ProfilePage`, `ProfileEditPage` | `auth` + `can:role-user` |
| `/shop/cart` | `ShopCartPage` | `auth` + `can:role-user` |
| `/shop/shipping` | `ShopShippingPage` | `auth` + `can:role-user` |
| `/shop/orders/{orderId}/checkout` | `ShopCheckoutPage` | `auth` + `can:role-user` |
| `/shop/orders` | `ShopOrdersPage` | `auth` + `can:role-user` |
| `/shop/orders/{orderId}` | `ShopOrderDetailPage` | `auth` + `can:role-user` |

Auth routes: เปิดเฉพาะ login (ปิด register/verify/reset)

### Central routes
- `web.php`: `/` (landing `frontend.central-domain`), `/{tenant_id}/shop/orders/{order_id}` (redirect invoice ไป tenant domain), `/error/{code}`
- `api.php`: `POST /webhooks/bcel`, `POST /webhooks/jdb`, `GET|POST /webhooks/hal`, `POST /flush-cache`

## 4. Middleware ([`app/Http/Middleware/`](../app/Http/Middleware/))

| Middleware | หน้าที่ |
|-----------|---------|
| `AddRequestContext` | ใส่ `request_id`, `user_id` ลง Context (logging/tracing) |
| `CheckProfile` | ถ้า user login แต่ profile ไม่ครบ → บังคับ redirect ไป `/edit-profile` (guest ผ่านได้, ยกเว้นหน้า edit-profile เอง) |
| `CheckTenantStatus` | ถ้า `tenant()->status === 'inactive'` → redirect ไป main domain |

## 5. Authentication (ล็อกอินด้วย OTP)

ระบบ **ไม่มีฟอร์ม username/password** — ล็อกอินด้วย **เบอร์โทร + OTP** ผ่าน component `OtpLoginModal`

Flow:
1. ผู้ใช้กรอกเบอร์ → validate รูปแบบเบอร์ลาว (`20[25789]\d{7}` หรือ `30[59]\d{6}`)
2. Rate limit 5 ครั้ง/นาที/เบอร์
3. สร้าง OTP 6 หลัก (หมดอายุ 30 นาที) ด้วย `tzsk/otp`, hash = `sha256(phone)`
4. ส่ง SMS ผ่าน **Telbiz** (หลัก) / **LTC** (สำรอง) — dev จะ log OTP แทนการส่งจริง
5. บันทึกลง `otp_logs`
6. ผู้ใช้กรอก OTP → verify → ถ้าไม่มี user ให้สร้างใหม่ (`type=phone`, `role=user`, `status=active`) → เช็ค `banned_at`/`status` → login + regenerate session
7. redirect กลับหน้าที่ตั้งใจไป (checkout/profile)

### Authorization
- Gate `role-user` ([`AuthServiceProvider`](../app/Providers/AuthServiceProvider.php)) — เช็ค `$user->role === 'user'`
- Role ที่มี: `user`, `admin`, `staff` (หน้าร้านฝั่งลูกค้าใช้ `role-user`)

### LoginController ([`app/Http/Controllers/Auth/LoginController.php`](../app/Http/Controllers/Auth/LoginController.php))
ใช้ trait `AuthenticatesUsers` มาตรฐาน แต่ปรับให้ทำงานร่วมกับ OTP modal (redirect home พร้อม `?action=login`)

## 6. Realtime (Ably + Laravel Echo)

- Event `OrderPaid` ([`app/Events/OrderPaid.php`](../app/Events/OrderPaid.php)) — implements `ShouldBroadcast`
- Broadcast บน private channel `orders.{orderId}` ([`routes/channels.php`](../routes/channels.php)) — เฉพาะเจ้าของออเดอร์ subscribe ได้
- หน้า `ShopCheckoutPage` ฟัง event นี้ผ่าน Echo → เมื่อจ่ายเงินสำเร็จจะแสดงผลทันทีโดยไม่ต้อง refresh
- Config: [`config/broadcasting.php`](../config/broadcasting.php) (รองรับ Ably/Pusher/Reverb/Redis/log — ใช้ Ably ผ่าน `ABLY_KEY`)
