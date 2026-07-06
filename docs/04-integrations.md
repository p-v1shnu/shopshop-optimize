# 04 — การเชื่อมต่อภายนอก (External Integrations)

Utility classes ทั้งหมดอยู่ที่ [`app/Utils/`](../app/Utils/) — config อยู่ที่ [`config/custom.php`](../config/custom.php) และ [`config/otp.php`](../config/otp.php)

## 1. Payment — BCEL OnePay QR

- Utility: [`app/Utils/BCELUtil.php`](../app/Utils/BCELUtil.php) · Worker: [`workers/bcel.cjs`](../workers/bcel.cjs) · Webhook: `WebhookController::apiUpdateBcel`

**Flow:**
1. **สร้าง QR** — `BCELUtil::generateQr()` สร้าง EMV QR string (TLV: tag 00 merchant id, 01 country, 33 merchant account, 53 currency, 54 amount, 58 merchant name, 63 CRC16 checksum)
2. **ฟังการจ่ายเงิน** — worker Node.js subscribe PubNub channel `mcid-{MC_ID}-{MC_CODE}` (ย้อนหลัง 60 นาที)
3. **แจ้งกลับ** — เมื่อมี notification worker สร้าง signature **HMAC-SHA256** ด้วย `APP_KEY` แล้ว POST ไป `/api/webhooks/bcel`
4. **Webhook** — verify signature (`hash_equals`), ตรวจ bill number/amount/เวลา, สร้าง `ShopOrderPayment` (channel `onepay`, provider `bcel`), dispatch event `OrderPaid`

**Env:** `BCEL_QR_PUBNUB_SUBKEY`, `BCEL_QR_PUBNUB_USERID`, `BCEL_QR_MC_ID`, `BCEL_QR_MC_CODE`, `BCEL_QR_MC_NAME`, `BCEL_QR_MCC`, `APP_KEY`

> มี command `ReconcileOnepay` สำรองไว้ query สถานะจาก BCEL OnePay API เผื่อ webhook พลาด (ดู [05](05-infrastructure-deployment.md))

## 2. Payment — JDB / Lao QR

- Utility: [`app/Utils/JDBUtil.php`](../app/Utils/JDBUtil.php) · Worker: [`workers/jdb.cjs`](../workers/jdb.cjs) · Webhook: `WebhookController::apiUpdateJdb`

**Flow:**
1. **Access token** — `JDBQRGetAccessToken()` เรียก `/autenticate`, cache (expiry − 100 วินาที)
2. **สร้าง QR** — `JDBQRGenerateQr()` เรียก `/generateQr` พร้อม bill number + amount, sign ด้วย **HMAC-SHA256** (`jdb_qr_sign_key`) ใส่ header `SignedHash`
3. **ฟังการจ่ายเงิน** — worker subscribe PubNub `mcid-{MC_ID}-{MC_CODE}`
4. **Webhook** — `apiUpdateJdb()` เรียก `JDBQRCheckTransaction()` ยืนยันการจ่าย → สร้าง `ShopOrderPayment` (channel `laoqr`, provider `jdb`)

**Env:** `JDB_QR_API_URL`, `JDB_QR_PUBNUB_SUBKEY`, `JDB_QR_PUBNUB_USERID`, `JDB_QR_MC_ID`, `JDB_QR_MC_CODE`, `JDB_QR_MC_NAME`, `JDB_QR_PARTNER_ID`, `JDB_QR_CLIENT_ID`, `JDB_QR_CLIENT_SECRET`, `JDB_QR_SIGN_KEY`, `JDB_QR_TERMINAL_ID`

> **หมายเหตุ:** ใน [`ecosystem.config.cjs`](../ecosystem.config.cjs) worker `jdb` ถูก **comment ไว้** (เปิดเฉพาะ bcel) — ถ้าจะใช้ JDB ต้องเปิด process นี้

## 3. Shipping — HAL Logistics

- Utility: [`app/Utils/HalUtil.php`](../app/Utils/HalUtil.php) · Controller: [`app/Http/Controllers/HALController.php`](../app/Http/Controllers/HALController.php) · Components: `HalBranchSelector`, `HalShipmentTracking`

**Flow:**
1. **Auth** — `HalUtil::getToken()` OAuth2 password grant → `https://hal.hal-logistics.la/oauth/token`, cache (หมดก่อนจริง 10 นาที)
2. **สาขา** — `HalBranchSelector` ดึง `/api/v1/listing/branches?is_within=true&is_active=true` (cache 60 นาที tag `HAL`) → แยกเป็นจังหวัด/อำเภอ/สาขา
3. **ตอนสร้างออเดอร์** — เรียก calculate-freight + create-pre-order → ได้ tracking number
4. **Tracking** — `HalShipmentTracking` เรียก `/api/v1/orders/tracking/{shipment_number}` (cache 5 นาที)
5. **Webhook** (`/api/webhooks/hal`):
   - `GET` (`webhookGet`) — verify `x_verify_secret` ตอบกลับ `x_challenging_id` (HAL challenge)
   - `POST` (`webhookPost`) — verify HMAC-SHA256 (`hal_sign_secret`) *[ปัจจุบัน comment bypass ไว้]* → อัปเดต `shipping_status` ตาม `shipment_status_id` (1=pending, 2=shipping, 3=completed) + บันทึก `ShopOrderLog`

**Env:** `HAL_CLIENT_ID`, `HAL_CLIENT_SECRET`, `HAL_USERNAME`, `HAL_PASSWORD`, `HAL_VERIFY_SECRET`, `HAL_SIGN_SECRET`, `HAL_WEBHOOK_URL`

Log request/response ทั้งหมดลงตาราง `shipping_logs` พร้อม response time

**ช่องทางขนส่งอื่น** (กำหนดที่ `tenant.shipping_channels`):
- `seller` — ร้านจัดส่งเอง (ผู้ใช้เลือกจังหวัด/อำเภอ/หมู่บ้าน, mark completed ทันที)
- ไม่มี channel — แบบ pickup ใช้ที่อยู่/พิกัดร้าน

กฎค่าส่งกำหนดผ่าน model `ShopShippingRule` (ยอดขั้นต่ำ + fee type: cod/free/prepaid)

## 4. SMS / OTP

- Utility: [`app/Utils/OtpUtil.php`](../app/Utils/OtpUtil.php) · Component: `OtpLoginModal` · Package: `tzsk/otp` · Config: [`config/otp.php`](../config/otp.php)

**Telbiz (หลัก):**
- ขอ JWT token จาก `{base_uri}connect/token` (client credentials) cache 30 นาที เช็ค exp ก่อนใช้ซ้ำ (ล้าง cache อัตโนมัติเมื่อเจอ 401)
- ส่ง SMS: POST `{base_uri}smsservice/newtransaction?subject={subject}` (Bearer token)
- **Env:** `TELBIZ_BASE_URI`, `TELBIZ_CLIENT_ID`, `TELBIZ_CLIENT_SECRET`, `TELBIZ_SUBJECT`

**LTC SMS (สำรอง):**
- เติมรหัสประเทศ `856`, transaction id = `LTC{timestamp}{random}`
- POST `LTC_SMS_URL` header `Apikey`
- **Env:** `LTC_SMS_URL`, `LTC_SMS_API_KEY`, `LTC_SMS_HEADER`

OTP: 6 หลัก หมดอายุ 30 นาที, rate limit 5/นาที, บันทึกลง `otp_logs` (ดูรายละเอียด flow ใน [01](01-architecture.md#5-authentication-ล็อกอินด้วย-otp))

## 5. Signature & Key management (Invoice / Stock API)

- Utility: [`app/Utils/WebhookUtil.php`](../app/Utils/WebhookUtil.php) · ตัวอย่าง verify ฝั่ง client: [`sample/verifyStockApiSignature.js`](../sample/verifyStockApiSignature.js)

ระบบส่ง webhook แจ้งออเดอร์ที่จ่ายแล้วไปยัง `tenant.order_invoice_webhook_url` โดย **sign ด้วย RSA private key (SHA256)** → ปลายทาง verify ด้วย public key

**สร้าง keypair** (เก็บที่ `storage/app/keypairs/`):
```bash
openssl genrsa -out shopshop_private_key.pem 2048
openssl rsa -in shopshop_private_key.pem -pubout -out shopshop_public_key.pem
```
**Env:** `PUBLIC_KEY_NAME` (default `shopshop_public_key.pem`), `PRIVATE_KEY_NAME` (default `shopshop_private_key.pem`)

การส่ง webhook จริงทำโดย command `SendInvoiceWebhook` (ดู [05](05-infrastructure-deployment.md))

## 6. Cloudflare (flush cache)

- Controller: [`app/Http/Controllers/CloudflareController.php`](../app/Http/Controllers/CloudflareController.php) · Endpoint: `POST /api/flush-cache`
- verify header `X-Secret` (`hash_equals`) → POST purge_cache ไป Cloudflare API
- **Env:** `FLUSH_CACHE_SECRET`, `CLOUDFLARE_API_TOKEN`, `CLOUDFLARE_ZONE_ID`
- Command `ClearHalCache` flush cache tag `HAL` แยกต่างหาก

## สรุป endpoint / provider

| Provider | ประเภท | Endpoint ในระบบ | ช่องทางรับผล |
|----------|--------|-----------------|--------------|
| BCEL OnePay | Payment | `POST /api/webhooks/bcel` | PubNub → worker → webhook |
| JDB / Lao QR | Payment | `POST /api/webhooks/jdb` | PubNub → worker → webhook |
| HAL Logistics | Shipping | `GET|POST /api/webhooks/hal` | HAL webhook |
| Telbiz / LTC | SMS/OTP | (outbound เท่านั้น) | - |
| Cloudflare | CDN | `POST /api/flush-cache` (outbound purge) | - |
| Invoice webhook | Outbound | ไปยัง `tenant.order_invoice_webhook_url` | RSA signed |
