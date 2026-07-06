# 02 — ฐานข้อมูล (Database)

Migrations อยู่ที่ [`database/migrations/`](../database/migrations/) เรียงลำดับ `0001`–`0008` ทุกตารางอยู่ในฐานข้อมูลเดียว (MySQL) โดยตารางฝั่งร้านค้าแยกข้อมูลรายร้านด้วยคอลัมน์ `tenant_id`

## ตารางแบ่งเป็น 2 กลุ่ม

### Central (ใช้ร่วมทุก tenant)
`tenants`, `domains`, `users`*, `settings`, `otp_logs`, `webhook_logs`, `shipping_logs`

> \* `users` มี `tenant_id` แยกรายร้าน แต่ตารางอยู่ในฐานกลาง (unique ต่อ tenant ดูด้านล่าง)

### Tenant-scoped (แยกด้วย `tenant_id`)
`shop_products`, `shop_orders`, `shop_order_details`, `shop_order_payments`, `shop_order_logs`, `shop_order_coupons`, `shop_coupons`, `shop_product_stocks`, `shop_shipping_rules`, `shop_user_searches`

> ทุกตาราง tenant-scoped มี FK `tenant_id → tenants.id` แบบ `onDelete: restrict` (ลบ tenant ที่มีข้อมูลไม่ได้)

## ER สรุปความสัมพันธ์หลัก

```
Tenant 1─┬─* Domain
         ├─* User ──────* ShopOrder ─┬─* ShopOrderDetail ──* ShopProduct
         │                           ├─1 ShopOrderPayment
         │                           ├─* ShopOrderCoupon ──* ShopCoupon
         │                           └─* ShopOrderLog
         ├─* ShopProduct ──* ShopProductStock (ledger สต็อก)
         ├─* ShopCoupon
         ├─* ShopShippingRule
         └─* ShopUserSearch
```

## Models หลัก ([`app/Models/`](../app/Models/))

### Tenant / User
- **Tenant** — extends BaseTenant, id เป็น string, เก็บ config รายร้าน (ดู [01-architecture.md](01-architecture.md))
- **User** — `type` (email/facebook/loca/phone), `role` (user/admin/staff), `status`, `banned_at`, ข้อมูลที่อยู่ (province 2 ตัวอักษร/district/village), `gender` (M/F/L)
  - computed `had_complete_profile` = ต้องมี phone, name, dob, gender, province, district, village ครบ
  - unique: `(tenant_id, email)`, `(tenant_id, phone)`
  - relation: `shopOrders()`

### Product & Stock
- **ShopProduct** — `name`, `images` (JSON), `normal_price` (ราคาเต็ม), `price` (ราคาขาย), `sku`, `available_quantity`, `status`, `sort_no`, `total_search`
  - append `cover_image` (จาก images[0])
  - method `updateProductAvailableQuantity($qty, $type, $remark)` → เรียก **stored procedure** (ดูด้านล่าง)
  - unique: `(tenant_id, sku)`
- **ShopProductStock** — *ledger การเคลื่อนไหวสต็อก* (audit trail): `quantity` (บวก/ลบได้), `shop_order_id` (nullable), `xref` (unique, กันบันทึกซ้ำ), `remark`

### Order
- **ShopOrder** — **PK เป็น Nanoid string** (ไม่ auto-increment), `$timestamps = false` (จัดการ created/updated เอง)
  - เงิน: `order_amount`, `shipping_amount`, `coupon_amount`, `payment_amount`
  - payment: `payment_status` (pending/paid/expired/cancelled/refunded), `payment_uuid` (unique), `payment_expired_at`, `payment_reconciled_at`, `payment_channel`
  - shipping: `shipping_fee_type` (cod/free/prepaid), `shipping_channel` (+ `_name`), ข้อมูลผู้รับ, `shipping_branch_*` (สำหรับ HAL), `shipping_detail` (JSON), `shipping_tracking_number` (unique), `shipping_status` (pending/shipping/completed)
  - QR: `generate_qr_request`, `generate_qr_response` (JSON)
  - อื่น ๆ: `order_code` (6 หลัก nanoid), `campaign_code`, `notified_invoice_api_at`
  - relations: `details()`, `user()`, `coupons()`, `payment()` (type='payment')
  - **composite index `payment_reconcile`** = `(payment_status, payment_expired_at, payment_reconciled_at)` ใช้เร่ง query การ reconcile/เคลียร์ออเดอร์
- **ShopOrderDetail** — line item: `shop_product_id`, `quantity`, `price`, snapshot `name`/`images`; unique `(shop_order_id, shop_product_id)`
- **ShopOrderPayment** — transaction จ่ายเงิน: `channel`, `merchant_provider`, `merchant_id`, `amount`, `xref`/`ref` (unique), `type` (payment/shipping_fee), `response` (JSON)
- **ShopOrderLog** — audit trail ของออเดอร์: `type`, `detail` (JSON), `response_time`
- **ShopOrderCoupon** — *snapshot* คูปองตอนใช้กับออเดอร์ (audit): เก็บ `coupon_code`, `coupon_type`, `discount_amount`, `before_discount_amount` ฯลฯ; unique `(shop_order_id, shop_coupon_id)`

### Coupon & Shipping rules
- **ShopCoupon** — `code` (unique ต่อ tenant), `type` (fixed/percentage), `amount`, ช่วงเวลา `started_at`/`ended_at`, `total_quantity`/`available_quantity` (1000000 = ไม่จำกัด), `user_daily_limit`, `minimum_order_amount`, `user_id` (null = คูปองสาธารณะ / มีค่า = คูปองเฉพาะบุคคล), `status` (active/inactive/expired/sold_out)
- **ShopShippingRule** — กฎค่าส่งตามช่วงเวลา + ยอดขั้นต่ำ: `started_at`/`ended_at`, `minimum_amount`, `shipping_fee_type` (cod/free/prepaid), `shipping_days_text`

### Log models (central)
- **OtpLog** — log การส่ง OTP (`provider`, `msisdn`, `otp`, `expired_at`, `data`)
- **WebhookLog** — log webhook ขาเข้า (`type`, `message`, `detail`, `model`, `model_id`)
- **ShippingLog** — log request/response ของ shipping API (`provider`, `type`, `data`)
- **Setting** — global setting (`title`, `facebook_cover_url`, `landing_page_url`) เข้าถึงผ่าน helper `setting()`

### Concern
- **SerializesDatesToAppTimezone** ([`app/Models/Concerns/`](../app/Models/Concerns/)) — override `serializeDate()` ให้ format วันที่เป็น timezone ของแอป (`Y-m-d\TH:i:sP`) ใช้ทุก model

## Stored Procedure: `update_product_available_quantity`

สร้างใน migration `0006` (ผ่าน `DB::unprepared()`) — ปรับสต็อกแบบ atomic ป้องกัน race condition

```sql
CREATE PROCEDURE update_product_available_quantity(
    IN  p_product_id BIGINT UNSIGNED,
    IN  p_quantity   INT,
    IN  p_type       ENUM('UPDATE','SET'),  -- UPDATE = บวก/ลบ, SET = เซ็ตค่าตรง
    IN  p_remark     TEXT,
    OUT p_success    BOOLEAN,               -- 0/1
    OUT p_message    TEXT
)
```

- ล็อกแถวสินค้าด้วย `FOR UPDATE` + ครอบ transaction
- ตรวจว่าสินค้ามีจริง + กันไม่ให้สต็อกติดลบ
- **side effect:** insert แถวลง `shop_product_stocks` เป็น audit trail
- เรียกใช้ผ่าน `ShopProduct::updateProductAvailableQuantity()`

การเรียกจาก SQL:
```sql
CALL update_product_available_quantity(1, 10, 'UPDATE', 'Stock added', @success, @message);
SELECT @success, @message;
```

## Triggers (MySQL)

| Trigger | ตาราง | หน้าที่ |
|---------|-------|---------|
| `shop_coupons_validate_amount_before_insert` / `_before_update` | `shop_coupons` | ตรวจ `amount >= 0`; ถ้า type=percentage ต้อง `<= 100` |
| `prevent_overlap_insert` / `prevent_overlap_update` | `shop_shipping_rules` | กันไม่ให้ rule ที่ active มีช่วงเวลาซ้อนกันภายใน tenant เดียวกัน (SQLSTATE 45000) |

## Seeders & Factories

- **DatabaseSeeder** → เรียก `ShopSeeder` เสมอ, และ `UserSeeder` + `ShopCouponSeeder` เฉพาะ local
- **ShopSeeder** — สร้าง Setting + tenant ตัวอย่าง 3 ร้าน:
  - `babybright` (เครื่องสำอาง, ขนส่ง `hal`, หลายจังหวัด) — domain `babybright.shopshop.test`
  - `muanson` (ยางรถ, แบบ pickup no_shipping มีพิกัด) — domain `muanson.shopshop.test`
  - `gadzila` (มือถือ, ขนส่ง `seller`) — domain `gadzila.shopshop.test`
- **UserSeeder** — user ทดสอบสำหรับ `gadzila`
- **ShopCouponSeeder** — คูปอง 2 ใบ/ร้าน (fixed + percentage) valid 2026-04-01 ถึง 2026-12-31
- **UserFactory** — สร้าง user สุ่ม (password ดีฟอลต์ = `password`)

## Config ที่เกี่ยวข้อง
- [`config/database.php`](../config/database.php) — connection ดีฟอลต์จาก env, Redis (db 0 = default, db 1 = cache) prefix `shopshop_database_`
- [`config/tenancy.php`](../config/tenancy.php) — migration path ชี้ไป `database/migrations/tenant` (ยังว่าง), seeder class = `DatabaseSeeder`
