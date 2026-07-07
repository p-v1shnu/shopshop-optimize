# 10 — PRD: Backoffice v1 (lean)

> **Status:** v1 core (M0–M5) ✅ เสร็จแล้ว · v1.1 (M6–M10) 🚧 กำลังทำ · **Scope:** ระบบหลังบ้าน (admin)
> เอกสารนี้ตอบ **"ต้องได้อะไร + เสร็จหน้าตาไหน" (มุมผู้ใช้)** — คู่กับ [08-backoffice-plan.md](08-backoffice-plan.md) ที่ตอบ **"สร้างยังไง" (สถาปัตยกรรม)** และ [09-future-improvements.md](09-future-improvements.md) (สิ่งที่เลื่อน)
> ใช้เป็น spec กลางสำหรับทั้ง Claude และ Codex — acceptance criteria คือเกณฑ์ว่างาน "เสร็จ"

## 1. เป้าหมาย
ปัจจุบัน ShopShop ไม่มีหลังบ้าน — config hardcode และสร้างข้อมูลผ่าน seeder เท่านั้น v1 ต้องการหลังบ้านที่ทำให้:
- เจ้าของร้านจัดการ **สินค้า/สต็อก/ออเดอร์** ของตัวเองได้เอง
- ตั้งค่าที่เคย hardcode (เปิด-ปิดร้าน/campaign) ได้จาก UI
- BIZGITAL ดูแลได้ทุกร้าน + สร้าง/จัดการบัญชีแอดมิน

## 2. Personas
| Persona | คือใคร | เป้าหมายหลัก |
|---------|--------|--------------|
| **Super-admin** | BIZGITAL (ผู้ดูแลแพลตฟอร์ม) | ดู/จัดการได้ทุกร้าน, สลับร้าน, สร้างบัญชีแอดมินร้าน, ช่วย refund |
| **Shop-admin** | เจ้าของ brand / คนที่ร้านมอบหมาย | จัดการสินค้า/สต็อก/ออเดอร์/settings ของร้านตัวเอง |

## 3. Scope

### v1 core (M0–M5) ✅ เสร็จแล้ว
- Auth + layout + shop switcher + tenant scoping (M0)
- จัดการบัญชีแอดมิน (M1)
- Settings / de-hardcode (M2)
- สินค้า + สต็อก (M3)
- ออเดอร์ + refund แบบ manual (M4)
- Dashboard เบา ๆ (M5)

### v1.1 (M6–M10) 🚧 wave ถัดไป — ปลดล็อกงานที่ยังต้องแก้ผ่าน DB/seeder
- คูปอง (M6), กฎค่าส่ง (M7), ลูกค้า/ban (M8), จัดการ brand/tenant+domain+config (M9, super), แบนเนอร์ (M10)
- ทั้งหมด **schema-minimal** (ตาราง/คอลัมน์มีครบแล้ว ไม่ต้อง migration)

### ยังไม่อยู่ใน scope (→ [09-future-improvements.md](09-future-improvements.md))
- refund state machine + **import CSV ธนาคาร** แบบ bulk (ตอนนี้ทำ manual ทีละออเดอร์)
- staff + สิทธิ์ย่อย, แอดมิน 1 คนหลายร้าน, audit log, ภาษาไทย, category สินค้า, แก้เนื้อหาออเดอร์
- database-per-tenant, 2FA, thumbnail/variant รูป

## 4. Requirements ต่อโมดูล (user stories + acceptance criteria)

### M0 — Auth & Foundation
**Story:** ในฐานะแอดมิน ฉันอยาก login ด้วย email/password เพื่อเข้าหลังบ้านตามสิทธิ์ของฉัน
**AC:**
- [ ] เข้าหลังบ้านที่ subdomain `admin.` (local `admin.shopshop.test:8899`), login ด้วย email+password (ไม่ใช่ OTP)
- [ ] ตาราง `admins` (email, password, role `super`/`shop`, tenant_id nullable) + seeder super-admin `pele@bizgital.com`
- [ ] guard แยกจากลูกค้า, logout ได้, กันเข้าถ้าไม่ login
- [ ] **super**: มี shop switcher (dropdown เลือกร้าน) → กำหนด "ร้านที่กำลังจัดการ" (session)
- [ ] **shop**: ถูกล็อกที่ร้านตัวเอง (ไม่มี switcher, สลับร้านไม่ได้)
- [ ] ทุกหน้า/query scope ด้วย tenant_id ของร้านที่กำลังจัดการ (แอดมินร้าน A เห็นข้อมูลร้าน A เท่านั้น)
- [ ] UI ภาษาอังกฤษ

### M1 — จัดการบัญชีแอดมิน (super เท่านั้น)
**Story:** ในฐานะ super-admin ฉันอยากสร้าง/จัดการบัญชีแอดมินร้าน เพื่อให้เจ้าของร้านเข้าใช้เองได้
**AC:**
- [ ] super เห็นรายการ admin ทั้งหมด, สร้างใหม่ (email, name, role, ผูก 1 ร้าน), ตั้ง/รีเซ็ต password, เปิด-ปิด (status)
- [ ] shop-admin ผูกได้ 1 ร้าน (เลือกจาก tenant ที่มีอยู่)
- [ ] shop-admin เข้าเมนูนี้ไม่ได้
- [ ] v1 ไม่มี self-service reset password (super เป็นคนรีเซ็ตให้)

### M2 — Settings (de-hardcode, ต่อร้าน)
**Story:** ในฐานะแอดมิน ฉันอยากตั้งค่าร้านที่เคยฝังในโค้ด เพื่อไม่ต้องแก้โค้ดทุกครั้ง
**AC:**
- [ ] แก้ได้: เปิด-ปิดร้าน (`enable_shop`), เปิด-ปิดคูปอง (`enable_coupon`), **วันปิดร้าน** (แทน hardcode `2030-01-30`), **campaign code/ช่วงเวลา** (แทน hardcode ปี 2025)
- [ ] ค่าใหม่เก็บใน `tenants.data` (JSON) — ไม่ต้อง migration
- [ ] `ShopUtil::isShopClosed()` / campaign logic อ่านจาก setting นี้แทนค่า hardcode (หน้าร้านลูกค้าทำงานตามค่าใหม่)

### M3 — สินค้า + สต็อก
**Story:** ในฐานะแอดมินร้าน ฉันอยากจัดการสินค้าและปรับสต็อก เพื่อดูแลแคตตาล็อกร้าน
**AC:**
- [ ] รายการสินค้า (ค้นหา/ฟิลเตอร์ status, เรียงตาม sort_no), แบ่งหน้า
- [ ] สร้าง/แก้สินค้า: name, `normal_price`, `price`, sku (unique ต่อร้าน), short/long desc, unit fields, storage, sort_no, status (active/inactive)
- [ ] อัปโหลดรูปหลายรูป + เลือก cover (เก็บผ่าน S3 driver: local MinIO / prod R2, ตาม [08 §6](08-backoffice-plan.md))
- [ ] ปรับสต็อกผ่าน stored procedure `update_product_available_quantity` (โหมด UPDATE +/- และ SET) พร้อมใส่ remark
- [ ] ดูประวัติการเคลื่อนไหวสต็อก (`shop_product_stocks` ledger)
- [ ] ไม่มี category (v1 ใช้ sort_no)

### M4 — ออเดอร์ (+ refund manual)
**Story:** ในฐานะแอดมิน ฉันอยากดูและจัดการสถานะออเดอร์ เพื่อดำเนินการจัดส่ง/คืนเงิน
**AC:**
- [ ] รายการออเดอร์ + ฟิลเตอร์ (payment_status, shipping_status, ช่วงวันที่, ค้นหา order code/เบอร์), แบ่งหน้า
- [ ] หน้ารายละเอียด: สินค้า, ยอดเงิน, การจ่าย, ที่อยู่/ขนส่ง — **read-only เนื้อหา** (แก้ item/จำนวน/ยอดไม่ได้)
- [ ] อัปเดต `shipping_status` + tracking number
- [ ] ส่ง invoice webhook ซ้ำ (ใช้ logic `SendInvoiceWebhook`)
- [ ] ยกเลิกออเดอร์ → คืนสต็อก + คืนคูปอง (logic แบบ `CleanUnPaidOrders`)
- [ ] **Refund (v1 manual):** ทั้ง super และ shop-admin ทำได้ → เปลี่ยน `payment_status` เป็น `refunded` + ป้อนเลข reference ธนาคาร + หมายเหตุ → บันทึกเป็นแถวใน `shop_order_logs` (type=`refund`, detail JSON) เป็นหลักฐาน — **ไม่แก้ schema เดิม**

### M5 — Dashboard (เบา)
**Story:** ในฐานะแอดมิน ฉันอยากเห็นภาพรวมร้านเมื่อเข้าหลังบ้าน
**AC:**
- [ ] การ์ดสรุป: ยอดขาย/จำนวนออเดอร์ (วันนี้/เดือนนี้), ออเดอร์รอจัดส่ง, สินค้าสต็อกใกล้หมด
- [ ] scope ตามร้านที่กำลังจัดการ

---

## v1.1 (M6–M10) — wave ถัดไป

> ทุกโมดูล: reuse pattern จาก M3/M4 (Livewire 4, current-shop scoping ผ่าน AdminTenantScope, ไม่ migration, ภาษาอังกฤษ, เขียน feature test + `Storage::fake('s3')` ถ้ามี upload)

### M6 — คูปอง (Coupons)
**Story:** ในฐานะแอดมินร้าน ฉันอยากสร้าง/จัดการคูปองของร้าน (ตอนนี้ Settings เปิด-ปิดคูปองได้ แต่สร้างคูปองได้ทาง seeder เท่านั้น)
**AC:**
- [ ] รายการคูปองของร้าน (ค้นหา code, ฟิลเตอร์ status/type), แบ่งหน้า
- [ ] สร้าง/แก้: `code` (unique ต่อร้าน), `type` (fixed/percentage), `amount`, `started_at`/`ended_at`, `total_quantity`, `available_quantity`, `user_daily_limit`, `minimum_order_amount`, `status`, `remark`; `user_id` nullable (ว่าง = คูปองสาธารณะ, เลือก user = คูปองส่วนตัว)
- [ ] validate ให้สอดคล้อง DB trigger (`amount >= 0`, percentage `<= 100`) — จับ error 45000/1644 มาแสดงอ่านง่าย ไม่ให้ 500
- [ ] ดูประวัติการใช้คูปอง (`shop_order_coupons` ของคูปองนั้น)
- [ ] scope ต่อร้าน, ไม่ migration

### M7 — กฎค่าส่ง (Shipping rules)
**Story:** ในฐานะแอดมินร้าน ฉันอยากตั้งกฎค่าส่งตามช่วงเวลา/ยอดสั่งซื้อ
**AC:**
- [ ] รายการ + สร้าง/แก้/ลบ: `status`, `started_at`/`ended_at`, `minimum_amount`, `shipping_fee_type` (cod/free/prepaid), `shipping_days_text`, `remark`
- [ ] มี DB trigger `prevent_overlap_*` กันช่วงเวลาซ้อนกัน (active) — จับ error 45000 มาแสดงข้อความอ่านง่าย ไม่ให้ 500
- [ ] scope ต่อร้าน, ไม่ migration

### M8 — ลูกค้า (Customers)
**Story:** ในฐานะแอดมิน ฉันอยากดูลูกค้าของร้านและระงับบัญชีที่มีปัญหา
**AC:**
- [ ] รายการ `users` (role=user) ของร้าน, ค้นหา (ชื่อ/เบอร์), แบ่งหน้า
- [ ] ดูรายละเอียดลูกค้า + ออเดอร์ของลูกค้า (read-only)
- [ ] **ban/unban** (`banned_at` + `status`) พร้อม remark
- [ ] scope ต่อร้าน, ไม่ migration; ไม่แก้โปรไฟล์ลูกค้า

### M9 — จัดการ Brand/Tenant (super เท่านั้น)
**Story:** ในฐานะ super-admin ฉันอยากสร้าง/แก้ brand + config โดยไม่ต้องพึ่ง seeder (เพื่อเปิดร้านใหม่ให้ลูกค้าได้)
**AC:**
- [ ] รายการ tenant ทั้งหมด, สร้าง tenant ใหม่ (`id`, `name`, `status`) + ผูก `domain` (ตาราง `domains`)
- [ ] แก้ config รายร้าน: `site_logo_url`(อัปโหลด), `facebook_*`, `google_tag_manager_id`, `google_analytics_id`, `shipping_channels`, `allow_province_ids`, `maintenance_mode`, `order_invoice_webhook_url`, `support_contact_phone`, `delivery_contact_phone`, `no_shipping_*` texts, `latitude`/`longitude` (pickup), `otp_site_name`, `contact_url`, `footer_*`, `head_html`, `title`
- [ ] อัปโหลดโลโก้ผ่าน S3 driver ที่มี
- [ ] shop-admin เข้าเมนูนี้ไม่ได้ (super เท่านั้น)
- [ ] ⚠️ สร้าง tenant **โดยไม่ trigger การสร้าง database แยก** — สถาปัตยกรรมเป็น single-DB + `tenant_id` (JobPipeline ของ `TenantCreated` ตั้งใจให้ว่าง); ยืนยันว่าหลังสร้าง tenant+domain แล้วหน้าร้านของ domain นั้น resolve ได้จริง
- [ ] ไม่ migration (ตาราง `tenants`/`domains` มีแล้ว)

### M10 — แบนเนอร์ (Banners)
**Story:** ในฐานะแอดมิน ฉันอยากจัดการแบนเนอร์หน้าแรก + popup ของร้าน
**AC:**
- [ ] แก้ `homepage_banners` + `popup_banners` ของร้าน (array บน `tenants`): อัปโหลดรูป (S3), จัดลำดับ, ลบ
- [ ] เก็บ URL รูปในรูปแบบเดิมที่หน้าร้านลูกค้าใช้ (ตรวจ shape กับ storefront blade ก่อน finalize)
- [ ] scope ต่อร้าน (แก้ tenant ปัจจุบัน), ไม่ migration

### Hardening (แทรกได้ทุกจุดของ v1.1) — จาก [09-future-improvements.md](09-future-improvements.md)
- [ ] Idempotency guards: `OrdersPage::cancelOrder()`/`refundOrder()` กันกดซ้ำ (ตอนนี้ restock/log ซ้ำได้)
- [ ] Admin self-lockout guard: กัน super ปิดบัญชีตัวเอง / ปิด super คนสุดท้าย
- [ ] เปิด verify HMAC signature ของ HAL webhook กลับ (ตอนนี้ comment bypass)

## 5. Non-functional
- **สกุลเงิน:** LAK (Lao Kip), จำนวนเงิน decimal 2 ตำแหน่ง · **Timezone:** Asia/Vientiane
- **สิทธิ์:** ทุก action ตรวจ role + tenant scope เสมอ (กันข้ามร้าน)
- **Performance:** ตารางใหญ่ใช้ pagination + index ที่มี; ไม่ N+1
- **Security:** password hash (bcrypt), CSRF, ไม่มี 2FA ใน v1
- **Responsive:** ใช้งานบน desktop เป็นหลัก (มือถือพอใช้ได้)
- **Stack:** custom Livewire 4 (ตาม [08 §7](08-backoffice-plan.md))

## 6. สมมติฐาน / ค่า default (ปรับได้)
- v1 core: brand (tenant) สร้างผ่าน seeder → **v1.1 (M9) เพิ่ม UI สร้าง brand** แล้ว
- ภาษาอังกฤษล้วน
- ไม่มี self-service password reset (super รีเซ็ตให้)
- refund ทำทีละออเดอร์ (CSV import ยังเลื่อน)

## 7. Open items (รอ/ยังไม่ล็อก)
- R2 credentials (account/bucket/key) สำหรับ storage prod
- ต้องมี audit log ไหม (ตอนนี้ยกไป future)
- ดู pattern refund/CSV จากโปรเจคขายบัตรเดิม (ถ้าเปิดให้)
