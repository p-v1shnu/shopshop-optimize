# 09 — แผนปรับปรุงอนาคต (Future Improvements / Backlog)

> รวมสิ่งที่ **ตั้งใจเลื่อนออกจาก v1** เพื่อให้ v1 แก้ schema น้อยที่สุดและอิงของเดิม (ดูหลักการใน [08-backoffice-plan.md §1.1](08-backoffice-plan.md))
> รายการเหล่านี้ส่วนใหญ่ต้อง **แก้โครงสร้างข้อมูล / เพิ่ม feature ใหญ่** — ทำเมื่อ v1 นิ่งแล้ว
>
> แต่ละข้อระบุ: *ทำไม* / *แนวทาง* / *กระทบ schema ไหม*

## A. Refund workflow เต็มรูปแบบ
**v1 ทำแค่:** mark `refunded` + log เลข ref ลง `shop_order_logs`
**อนาคต:**
1. **สถานะกลาง `refund_requested`** (แจ้งธนาคารแล้วรออยู่ ก่อนถึง `refunded`)
   - แนวทาง: เพิ่มค่าใน enum `payment_status` หรือทำ field แยก `refund_status` — *กระทบ schema (ALTER enum)*
2. **บันทึก refund เป็น transaction จริง** ใน `shop_order_payments`
   - เพิ่ม type `refund` ใน enum (`payment`/`shipping_fee`/`refund`), เก็บ amount, เลข ref ธนาคารใน `ref`, หลักฐานใน `response`/`remark`, `reconciled_at` — *กระทบ schema*
3. **`refunded_at`** เป็นคอลัมน์จริงใน `shop_orders` (แทนที่จะอ่านจาก log) — *กระทบ schema*
4. **Import CSV จากธนาคารแบบ bulk** — อัปโหลด CSV → จับคู่ order อัตโนมัติ (payment ref / order code) → เขียน refund reference ทีละหลายรายการ + สรุปผล match/unmatch
   - อ้างอิง pattern จาก **โปรเจคขายบัตรออนไลน์เดิมของเจ้าของ** (order/refund schema + โค้ด import) — reuse ของที่ใช้งานจริงแล้ว

## B. ระบบสิทธิ์ละเอียดขึ้น
**v1 ทำแค่:** 2 role — `super` / `shop`
**อนาคต:**
- **`staff`** ระดับร้าน + สิทธิ์ย่อย (เช่น ดูออเดอร์ได้แต่แก้สินค้าไม่ได้) — ใช้ permission/policy ละเอียด
- **แอดมิน 1 คนคุมหลายร้าน** — เพิ่มตาราง pivot `admin_tenant` (v1 = 1 คน/1 ร้าน) — *กระทบ schema (เพิ่มตาราง)*

## C. Audit log ของการกระทำในหลังบ้าน → **ยกเข้า scope แล้ว (v1.2 M14 ใน docs/10)**
- ตาราง `admin_activity_logs` (additive) + หน้า super ดู log — spec เต็มอยู่ใน docs/10

## D. ภาษา (i18n)
**v1 ทำแค่:** อังกฤษอย่างเดียว
**อนาคต:** เพิ่มไทย (+ ลาว?) ด้วย Laravel localization (`lang/en`, `lang/th`) + ตัวสลับภาษาในหลังบ้าน

## E. De-hardcode / Settings ให้เป็นระเบียบ
**v1 ทำแค่:** เก็บค่าใน `tenants.data` (JSON) เพื่อเลี่ยง migration
**อนาคต:**
- ถ้าค่าไหนใช้บ่อย/ต้อง query → ย้ายจาก JSON เป็นคอลัมน์จริง + index
- ตรวจหา hardcode จุดอื่นเพิ่ม (เช่น `getShippingDiscountAttribute()` ที่ return 0 เสมอ, cutoff อื่น ๆ) แล้วทยอยทำให้ configurable

## F. ระบบรูปภาพ
**v1 ทำแค่:** อัปโหลดขึ้น disk (MinIO local / R2 prod) เก็บ URL
**อนาคต:** สร้าง thumbnail/variant หลายขนาด, validate/บีบอัด, จัดการลบไฟล์เก่าเมื่อเปลี่ยนรูป

## G. ความปลอดภัย & คุณภาพ
- ~~**Order action idempotency guards**~~ → **ยกเข้า scope แล้ว (v1.2 H11 ใน docs/10)**
- ~~**Admin self-lockout guard**~~ → **ยกเข้า scope แล้ว (v1.2 H11)**
- ~~**เปิด HAL webhook signature**~~ → **ยกเข้า scope แล้ว (v1.2 H11)**
- **2FA** สำหรับ admin login *(ตัดสินใจแล้ว: ยังไม่ทำใน v1.2)*
- **เปิด verify HMAC signature ของ HAL webhook กลับ** (ตอนนี้ bypass อยู่ — ดู [06-open-questions.md](06-open-questions.md))
- **เริ่มเขียน test** (ยังไม่มีโฟลเดอร์ `tests/` เลย) — เริ่มจากส่วนที่แตะบ่อย (order, stock, refund)
- ทบทวน migration ต้นฉบับที่ไม่ portable (เจอแล้ว 1 จุด: boolean default string — แก้ไปแล้ว) เผื่อมีอีก

## G.1 Deprecation ที่เจอ (pre-existing)
- stancl/tenancy 3.10 บน PHP 8.4 ขึ้น deprecation: *"Accessing static trait property `BelongsToTenant::$tenantIdColumn` is deprecated"* (มาจากทุก model ที่ใช้ trait นี้ เช่น User, ShopProduct — มีมาตั้งแต่ baseline) ควรจัดการตอน upgrade stancl เวอร์ชันถัดไป

## H. โครงสร้าง tenancy
- ปัจจุบันเป็น single-database + `tenant_id` column (ไม่ใช่ database-per-tenant) — ถ้าจำนวนร้าน/ข้อมูลโตมาก อาจพิจารณาแยก database ต่อ tenant (stancl รองรับ) แต่เป็นงานใหญ่ ประเมินตอนสเกลจริง

---

## วิธีใช้เอกสารนี้
- เมื่อเจอไอเดีย/หนี้เทคนิคที่ตั้งใจเลื่อน → มาเพิ่มที่นี่ (ไม่ทำใน v1)
- เมื่อ v1 นิ่งแล้ว → หยิบทีละข้อมาทำ โดยข้อที่ *กระทบ schema* ควรทำเป็น migration + วางแผน merge กับ source เดิมให้ดี
