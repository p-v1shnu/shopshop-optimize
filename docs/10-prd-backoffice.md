# 10 — PRD: Backoffice v1 (lean)

> **Status:** draft v1 · **Scope:** ระบบหลังบ้าน (admin) เวอร์ชันแรก
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

### อยู่ใน v1
- Auth + layout + shop switcher + tenant scoping (M0)
- จัดการบัญชีแอดมิน (M1)
- Settings / de-hardcode (M2)
- สินค้า + สต็อก (M3)
- ออเดอร์ + refund แบบ manual (M4)
- Dashboard เบา ๆ (M5)

### ไม่อยู่ใน v1 (→ [09-future-improvements.md](09-future-improvements.md))
- สร้าง/แก้ **brand (tenant) + domain** ผ่าน UI — v1 ยังใช้ seeder (super-admin จัดการได้เฉพาะ "ร้านที่มีอยู่แล้ว")
- refund state machine + **import CSV ธนาคาร** แบบ bulk (v1 ทำ manual ทีละออเดอร์)
- staff + สิทธิ์ย่อย, แอดมิน 1 คนหลายร้าน, audit log, ภาษาไทย, category สินค้า, แก้เนื้อหาออเดอร์, คูปอง, กฎค่าส่ง, จัดการลูกค้า

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

## 5. Non-functional
- **สกุลเงิน:** LAK (Lao Kip), จำนวนเงิน decimal 2 ตำแหน่ง · **Timezone:** Asia/Vientiane
- **สิทธิ์:** ทุก action ตรวจ role + tenant scope เสมอ (กันข้ามร้าน)
- **Performance:** ตารางใหญ่ใช้ pagination + index ที่มี; ไม่ N+1
- **Security:** password hash (bcrypt), CSRF, ไม่มี 2FA ใน v1
- **Responsive:** ใช้งานบน desktop เป็นหลัก (มือถือพอใช้ได้)
- **Stack:** custom Livewire 4 (ตาม [08 §7](08-backoffice-plan.md))

## 6. สมมติฐาน / ค่า default (ปรับได้)
- brand (tenant) ยังสร้างผ่าน seeder ใน v1 — หน้า UI สร้าง brand เลื่อนไป future
- ภาษาอังกฤษล้วน
- ไม่มี self-service password reset (super รีเซ็ตให้)
- refund ทำทีละออเดอร์ (ยังไม่มี CSV import)

## 7. Open items (รอ/ยังไม่ล็อก)
- R2 credentials (account/bucket/key) สำหรับ storage prod
- ต้องมี audit log ไหม (ตอนนี้ยกไป future)
- ดู pattern refund/CSV จากโปรเจคขายบัตรเดิม (ถ้าเปิดให้)
