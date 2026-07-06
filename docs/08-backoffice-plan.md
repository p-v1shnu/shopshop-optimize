# 08 — แผนสร้าง Backoffice (Admin) — *แผนเท่านั้น ยังไม่ลงโค้ด*

> เอกสารนี้คือแผน/สถาปัตยกรรมสำหรับสร้างระบบหลังบ้าน (backoffice) ของ ShopShop
> **สถานะ: ยังไม่เริ่มเขียนโค้ด** — เป็นการสรุปการตัดสินใจและ roadmap ก่อนลงมือ

## 1. เป้าหมาย

ปัจจุบันระบบ**ไม่มีหลังบ้านเลย** — การตั้งค่าทุกอย่าง hardcode และการสร้าง brand/สินค้า/คูปองทำผ่าน seeder เท่านั้น เป้าหมายคือสร้าง backoffice เพื่อ:
1. ให้ตั้งค่าที่เคย hardcode ได้จาก UI
2. ให้เจ้าของร้านจัดการสินค้า/สต็อก/ออเดอร์/คูปอง/ค่าส่งของตัวเองได้
3. ให้ BIZGITAL (แอดมินใหญ่) สร้าง/จัดการ brand ทุกร้านได้

## 2. สถาปัตยกรรม: ระบบ admin เดียว + login เดียว

**ไม่แยก admin ตาม domain ของร้าน** — เป็นแอปแอดมิน**ระบบเดียว มีหน้า login เดียว** สิทธิ์ของบัญชีที่ login เป็นตัวกำหนดสิ่งที่เห็น/ทำได้

```
                 admin.shopshop.la   (login เดียว: email + password)
                          │
          ┌───────────────┴─────────────────┐
   role = super                      role = shop
   (แอดมินใหญ่ / BIZGITAL)            (แอดมินของร้าน)
          │                                 │
   • จัดการทุก brand/tenant        • เห็น/จัดการเฉพาะข้อมูลร้านตัวเอง
   • สลับเข้าดูร้านไหนก็ได้ (shop   • ถูกล็อกที่ร้านของตัวเอง
     switcher)                       • สลับร้านไม่ได้
   • ฟีเจอร์ระดับ platform
```

### หลักการเชิงเทคนิค
- Admin อยู่บน **central domain** → **ไม่ใช้** การ auto-detect tenant ตาม domain ของ stancl/tenancy
- Admin ทำงานใน **central context** แล้ว **scope ข้อมูลด้วย `tenant_id` เอง** ตาม "ร้านที่กำลังจัดการ" (เก็บใน session)
  - `super`: เลือกร้านผ่าน shop switcher (dropdown) → กำหนด current `tenant_id`
  - `shop`: current `tenant_id` = ร้านของตัวเองเสมอ (ล็อก)
- ทุก query/action ในหลังบ้านต้อง filter ด้วย current `tenant_id` (มี middleware/global scope กลางกันลืม)

## 3. Data model: ตาราง `admins` (central)

แยกจากตาราง `users` (ที่เป็น **ลูกค้า** login ด้วย OTP รายร้าน) — สร้างตารางกลางใหม่:

| คอลัมน์ | ชนิด | หมายเหตุ |
|---------|------|----------|
| id | bigint PK | |
| name | string | |
| email | string, unique | ใช้ login |
| password | string (hashed) | |
| role | enum(`super`,`shop`) | `super` = แอดมินใหญ่, `shop` = แอดมินร้าน |
| tenant_id | string, **nullable**, FK→tenants.id | `null` เมื่อ role=super, มีค่าเมื่อ role=shop |
| status | enum(active/inactive) | |
| last_login_at | datetime nullable | |
| timestamps | | |

> **ขอบเขต v1:** แอดมินร้าน 1 คน = 1 ร้าน (ค่า default) ถ้าอนาคตต้องการ 1 คนคุมหลายร้าน → เพิ่มตาราง pivot `admin_tenant` ทีหลัง (ออกแบบ schema เผื่อไว้ได้แต่ยังไม่ทำ)
>
> ตาราง `users.role` (มี enum admin/staff อยู่แล้วแต่ไม่ถูกใช้) — **จะไม่ reuse** เพื่อไม่ปนกับลูกค้า; ใช้ตาราง `admins` แยกชัดเจน

## 4. Authentication
- **Guard แยก** ชื่อ `admin` (แยกจาก guard `web` ของลูกค้า)
- Login ด้วย **email + password** (bcrypt) — ไม่ใช้ OTP
- Gate/Policy: `admin-super`, `admin-shop` + middleware บังคับ current tenant scope
- Seeder สร้างแอดมินใหญ่คนแรก (bootstrap)
- v1 ยังไม่ทำ 2FA (บันทึกเป็น backlog)

## 5. ภาษา UI
- **v1: อังกฤษอย่างเดียว** (เพื่อประหยัดเวลา/token ตอนพัฒนา — ต่างจากหน้าร้านลูกค้าที่เป็นภาษาลาว)
- ยังไม่ทำ i18n switcher; เขียน string อังกฤษไปก่อน
- ถ้าอนาคตต้องการไทยด้วย ค่อยเพิ่ม Laravel localization (`lang/en`, `lang/th`) ทีหลัง

## 6. Storage รูปภาพ (สร้างใหม่ — ของเดิมไม่มี)

**ผลตรวจสอบ:** โค้ดปัจจุบัน**ไม่มีระบบอัปโหลดรูป** — เก็บเป็น URL ชี้ไป `https://assets.shopshop.la/...` (ไม่มี `config/filesystems.php`, ไม่มี env AWS/S3, ไม่มีโค้ด Storage/S3; `aws-sdk-php` เป็นแค่ suggested dep ไม่ได้ติดตั้งจริง)

**Production ใช้ Cloudflare R2** — ยืนยันแล้วจากหน้า 404 ของ `assets.shopshop.la` (เป็น error page ของ Cloudflare R2 public bucket) สอดคล้องกับที่โปรเจคใช้ Cloudflare อยู่แล้ว (`CLOUDFLARE_API_TOKEN`, flush cache) **R2 เป็น S3-compatible** จึงใช้ S3 driver ได้ แค่ชี้ endpoint ไป R2

**แผน:** เพิ่มระบบอัปโหลดผ่าน Laravel filesystem abstraction แบบ **disk เดียว สลับด้วย env** (ทุกที่เป็น S3-compatible)
- publish `config/filesystems.php` + เพิ่ม disk `s3` (รองรับ custom `AWS_ENDPOINT` + `AWS_URL` สำหรับ R2/MinIO)
- **Local dev = MinIO** (S3-compatible) — เพิ่ม service ใน docker-compose → โค้ดชุดเดียวกับ prod
- **Production = Cloudflare R2** — เปลี่ยนแค่ env: `FILESYSTEM_DISK=s3`, `AWS_ENDPOINT=https://<account>.r2.cloudflarestorage.com`, `AWS_URL=https://assets.shopshop.la`, `AWS_ACCESS_KEY_ID`/`AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET`, `AWS_DEFAULT_REGION=auto`
- โค้ดใช้ `Storage::disk(...)->put(...)` แล้วเก็บ public URL (รูปแบบ `https://assets.shopshop.la/...`) ลง field `images`/`*_url` — คงรูปแบบ URL เดิมไว้ ให้หน้าร้านทำงานเหมือนเดิม
- ต้องขอจากทีม infra: R2 account id / bucket name / access key สำหรับ prod

## 7. Tech stack ของหลังบ้าน
- **Custom Livewire 4** (stack เดียวกับหน้าร้าน) — ไม่ใช้ Filament เพราะ Filament เดิมผูกกับ Livewire 3 อาจไม่เข้ากับ Livewire 4.3 ที่โปรเจคใช้ และเลี่ยงความเสี่ยง dependency
- Tailwind (มีอยู่แล้ว) + สร้าง component ตาราง/ฟอร์ม/modal ใช้ซ้ำเอง

## 8. Roadmap (แบ่ง phase)

### ✅ เลือกทำก่อน (ตามที่ตกลง)

**Phase 3.0 — รากฐาน admin**
- migration + model `admins`, seeder แอดมินใหญ่
- guard `admin`, หน้า login (email/password), logout
- layout หลังบ้าน + เมนูตามสิทธิ์ + i18n (en/th)
- กลไก "current shop" (session) + shop switcher (super) + middleware scope tenant_id
- dashboard เปล่า
- routing บน subdomain `admin.` (local: `admin.shopshop.test:8899`)

**Phase 3.1 — เก็บ hardcode → settings**
- ย้าย `ShopUtil` วันปิดร้าน (`2030-01-30 18:00:00`) + campaign codes (chinese/lao new year, 06_06) → tenant setting/config
- หน้า Settings แก้ค่าต่อร้านได้ (เปิด-ปิดร้าน, วันปิด, campaign, ฯลฯ)

**Phase 3.2 — สินค้า + สต็อก**
- CRUD สินค้า (name, prices, sku, short/long desc, sort_no, status, unit)
- อัปโหลดรูป (ตามข้อ 6) — cover + gallery
- ปรับสต็อกผ่าน stored procedure `update_product_available_quantity` (UPDATE/SET) + แสดง ledger `shop_product_stocks`

**Phase 3.3 — ออเดอร์**
- ตาราง + ฟิลเตอร์ (payment_status, shipping_status, วันที่, ค้นหา)
- หน้ารายละเอียด: รายการสินค้า, การจ่ายเงิน, ที่อยู่/ขนส่ง
- อัปเดต shipping_status / tracking number
- ส่ง invoice webhook ซ้ำ (ใช้ `SendInvoiceWebhook` logic)
- ยกเลิก/คืนออเดอร์ → คืนสต็อก + คืนคูปอง (ใช้ logic แบบ `CleanUnPaidOrders`)

#### การ refund (ไม่มี API — ทำ manual + เก็บหลักฐาน)
Flow จริง: ลูกค้าขอคืน → แจ้งข้อมูล order ให้ธนาคาร → ธนาคาร refund → ธนาคารส่ง **CSV** (มีเลข reference) → นำ ref มาป้อนเก็บเป็นหลักฐาน

สถานะที่มีในระบบ: `payment_status` มี `refunded` แล้ว **แต่ยังขาด** ที่เก็บเลข ref ธนาคาร, `refunded_at`, สถานะกลาง, และ type `refund` ใน `shop_order_payments`

สิ่งที่ต้องเพิ่ม (migration เล็ก ๆ):
- สถานะกลาง `refund_requested` ก่อน `refunded` (แจ้งธนาคารแล้วรออยู่) — เพิ่มใน enum หรือ field แยก `refund_status`
- บันทึกการคืนเงินเป็นแถวใน `shop_order_payments` (เพิ่ม type `refund`: amount, เลข ref ธนาคารใน `ref`, หมายเหตุ/ไฟล์ใน `remark`/`response`, `reconciled_at`) + `refunded_at` ใน order
- หน้า **import CSV จากธนาคาร** → จับคู่ order (payment ref / order code) → เขียน refund reference อัตโนมัติ
- อ้างอิงรูปแบบจากโปรเจคขายบัตรเดิมของเจ้าของ (ถ้าเข้าถึงได้) เพื่อ reuse pattern ที่ใช้งานจริงแล้ว

### 🔜 Phase ถัดไป (หลัง 4 อันแรก)
- **Phase 3.4 — Platform super-admin:** สร้าง/แก้ tenant + domain, ตั้ง config รายร้าน (โลโก้/banner/facebook/analytics/shipping_channels/allow_province_ids/maintenance/พิกัด pickup) — แทน seeder
- **Phase 3.5 — คูปอง + กฎค่าส่ง + ลูกค้า:** CRUD คูปอง, กฎค่าส่ง (ระวัง trigger overlap `prevent_overlap_*`), จัดการลูกค้า (ban/unban)
- **Phase 3.6 — Dashboard/รายงาน + test + polish:** ยอดขาย/ออเดอร์/สต็อกใกล้หมด, เขียน test, เก็บงาน

## 9. บันทึกการตัดสินใจ (Decisions log)

| หัวข้อ | สรุป |
|--------|------|
| Tech stack | Custom Livewire 4 (ไม่ใช้ Filament) |
| โครงสร้าง admin | ระบบเดียว + login เดียว, สิทธิ์กำหนดขอบเขต (ไม่แยกตาม domain ร้าน) |
| URL | subdomain — local `admin.shopshop.test:8899`, prod `admin.shopshop.la` |
| Roles | 2 ระดับ: `super` (ทุกร้าน) + `shop` (ร้านตัวเอง); staff ละเอียดไว้ทีหลัง |
| แอดมิน/ร้าน | v1: shop-admin 1 คน = 1 ร้าน (extend เป็น pivot ทีหลัง) |
| ภาษา UI | v1 อังกฤษอย่างเดียว (ประหยัด token; เพิ่มไทยทีหลังได้) |
| Storage รูป | สร้างใหม่; local=MinIO, **prod=Cloudflare R2** (ยืนยันแล้ว, S3-compatible), สลับด้วย env |
| ลำดับทำ | 3.0 → 3.1 → 3.2 → 3.3 (ที่เหลือตามมา) |

## 10. งานที่ต้องยืนยัน/ตรวจเพิ่มก่อน/ระหว่างทำ
- ~~infra ของ `assets.shopshop.la`~~ → **ยืนยันแล้ว = Cloudflare R2** เหลือขอ account id / bucket / access key สำหรับตั้ง env prod
- ~~บัญชีแอดมินใหญ่ชุดแรก~~ → **`pele@bizgital.com`** (super-admin, seed ใน Phase 3.0)
- ~~BCEL/JDB มี API refund ไหม~~ → **ไม่มี** ทำ manual: แจ้งธนาคาร → ธนาคาร refund + ส่ง CSV เลข ref → import เก็บหลักฐาน (ดู Phase 3.3)
- ขอเข้าถึงโปรเจคขายบัตรเดิมของเจ้าของ (order/refund schema + CSV import) เพื่อ reuse pattern — path/repo?
- ต้องมี audit log ของ action ในหลังบ้านไหม (backlog)
