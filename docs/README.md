# ShopShop — เอกสารระบบ (System Documentation)

> เอกสารชุดนี้อธิบายโครงสร้างและวิธีทำงานของระบบ **ShopShop** ทั้งหมด จัดทำขึ้นเพื่อการรับช่วงต่อโปรเจคจาก outsource developer

## ShopShop คืออะไร

**ShopShop** คือแพลตฟอร์มขายของออนไลน์แบบ **multi-brand / multi-tenant** (คล้าย Shopify) สำหรับตลาด **ประเทศลาว** โดยแต่ละ brand จะเป็น *tenant* แยกกันอย่างสมบูรณ์:

- แยกตาม **domain / subdomain** (เช่น `babybright.shopshop.la`, `muanson.shopshop.la`)
- แยก **ฐานข้อมูล** และ config รายร้าน (ธีม, โลโก้, banner, ช่องทางขนส่ง, ฯลฯ)
- ลูกค้าเข้าใช้งานผ่านเว็บ ล็อกอินด้วย **เบอร์โทร + OTP**, เลือกสินค้า → ตะกร้า → เลือกขนส่ง → สร้างออเดอร์ → จ่ายเงินผ่าน **QR ธนาคาร (BCEL / JDB)** → ติดตามพัสดุ

## Technology Stack

| ส่วน | เทคโนโลยี |
|------|-----------|
| Backend framework | **Laravel 13** (PHP 8.4) |
| Frontend / UI | **Livewire 4** + Blade + Vue 2 (บาง component) + Tailwind CSS 3 |
| Multi-tenancy | **stancl/tenancy 3.10** (แยกตาม domain) |
| Database | **MySQL** (+ Redis สำหรับ cache/queue) |
| Auth | Laravel + **Sanctum**, ล็อกอินด้วย OTP (`tzsk/otp`) |
| Realtime | **Ably** (Laravel Echo) — แจ้งเตือนเมื่อจ่ายเงินสำเร็จ |
| Payment | BCEL OnePay QR, JDB / Lao QR (EMV QR + PubNub listener) |
| Shipping | **HAL Logistics** API, Seller-direct, Pickup |
| SMS / OTP | **Telbiz** (หลัก), **LTC SMS** (สำรอง) |
| Background workers | **Node.js** (`workers/*.cjs`) รันด้วย **PM2**, ฟัง PubNub |
| Build tool | **Vite 5** |
| CDN / Cache | **Cloudflare** (มี endpoint flush cache) |

## สารบัญเอกสาร

1. **[01-architecture.md](01-architecture.md)** — ภาพรวมสถาปัตยกรรม, multi-tenancy, routing, middleware, auth
2. **[02-database.md](02-database.md)** — โครงสร้างฐานข้อมูล, models, ตาราง central vs tenant, stored procedure, triggers
3. **[03-frontend-shop-flow.md](03-frontend-shop-flow.md)** — flow การช้อปปิ้งตั้งแต่ต้นจนจบ + reference ของ Livewire component ทุกตัว
4. **[04-integrations.md](04-integrations.md)** — การเชื่อมต่อภายนอก: payment (BCEL/JDB), shipping (HAL), SMS/OTP, signature, Cloudflare
5. **[05-infrastructure-deployment.md](05-infrastructure-deployment.md)** — providers, console commands (scheduler), workers, PM2, build, deploy
6. **[06-open-questions.md](06-open-questions.md)** — คำถาม/ข้อควรตรวจสอบก่อนทำงานต่อ
7. **[07-local-docker.md](07-local-docker.md)** — วิธีรันในเครื่องด้วย Docker (แนะนำเริ่มที่นี่)
8. **[08-backoffice-plan.md](08-backoffice-plan.md)** — แผนสร้างระบบหลังบ้าน (admin) — ยังไม่ลงโค้ด
9. **[09-future-improvements.md](09-future-improvements.md)** — แผนปรับปรุงอนาคต (สิ่งที่เลื่อนจาก v1)
10. **[10-prd-backoffice.md](10-prd-backoffice.md)** — PRD (lean) ของ backoffice v1: user stories + acceptance criteria

## Quick Start (สำหรับ dev ที่รับช่วงต่อ)

```bash
# 1. ติดตั้ง dependencies
composer install
npm install

# 2. ตั้งค่า environment
cp .env.example .env
php artisan key:generate
# แก้ .env: DB, ABLY_KEY, BCEL_*, JDB_*, HAL_*, TELBIZ_*, LTC_* ตามค่าจริง

# 3. สร้าง RSA keypair สำหรับ signing (invoice webhook / stock API)
#    เก็บไว้ที่ storage/app/keypairs/
openssl genrsa -out shopshop_private_key.pem 2048
openssl rsa -in shopshop_private_key.pem -pubout -out shopshop_public_key.pem

# 4. migrate + seed (seeder สร้าง tenant ตัวอย่าง 3 ร้าน: babybright, muanson, gadzila)
php artisan migrate
php artisan db:seed

# 5. รัน dev
npm run dev          # Vite
php artisan serve    # หรือใช้ valet/nginx (ต้องตั้ง wildcard domain *.shopshop.test)

# 6. รัน payment worker (ฟัง PubNub)
pm2 start ecosystem.config.cjs
```

> **สำคัญ:** ระบบเป็น multi-tenant แบบ domain-based ต้องเข้าผ่าน **tenant domain** (เช่น `gadzila.shopshop.test`) ไม่ใช่ `localhost` — ดูรายละเอียดใน [01-architecture.md](01-architecture.md)
