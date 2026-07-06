# 06 — คำถาม & ข้อควรตรวจสอบ (Open Questions)

รายการนี้คือสิ่งที่ผมสังเกตจากโค้ดแล้วยัง "ตอบเองไม่ได้" ต้องอาศัยบริบทจากคุณหรือ outsource dev เดิม — แนะนำให้เคลียร์ก่อนเริ่มพัฒนาต่อ

## ✅ สรุปที่เคลียร์แล้ว (จากเจ้าของโปรเจค)

- **ยังไม่มีฝั่ง admin/backoffice** — โค้ดชุดนี้เป็นฝั่งลูกค้า + webhook เท่านั้น การตั้งค่าทุกอย่างตอนนี้ยัง **hardcode** อยู่ (นี่คือเหตุผลหลักที่รับโปรเจคมาทำต่อ โดยจะให้ AI ช่วยพัฒนา — คาดว่ารวมถึงการทำให้ config ปรับได้ + สร้างฝั่ง admin)
- **Credential จริง + RSA key** ของ production/UAT → มีครบแล้ว
- **Source control:** ต้นทางอยู่บน **GitLab** ของ outsource dev — แผนคือเอามาแก้ในเครื่อง → push ขึ้น **GitHub ของเจ้าของ** ก่อน → ทดสอบ → ค่อย merge กับของ outsource dev ภายหลัง
- **Server จริง:** ยังไม่ใช่เรื่องเร่งด่วน (จะทดสอบในเครื่อง/GitHub ก่อน) ไว้คุยตอนจะ deploy

## คำถามที่ยังเปิดอยู่ (ควรเคลียร์ก่อน/ระหว่างพัฒนาต่อ)

### Payment
1. Worker **JDB ถูก comment ไว้** ใน `ecosystem.config.cjs` — ตั้งใจปิด (ยังไม่เปิดใช้ JDB) หรือควรเปิด?
2. `ReconcileOnepay` **ไม่ได้อยู่ใน scheduler** (`routes/console.php`) — รันด้วย cron แยกไหม หรือยังไม่ได้เปิดใช้จริง?

### Shipping (HAL)
3. ใน `HALController::webhookPost` การ **verify HMAC signature ถูก comment (bypass) ไว้** — เป็นของชั่วคราวตอน dev หรือปล่อยแบบนี้บน production? (มีความเสี่ยงด้านความปลอดภัย ควรเปิดกลับ)
4. HAL webhook subscribe ผ่าน command `SubscribeHALWebhook` — ต้องรันเองหลัง deploy ทุกครั้งไหม?

### Business logic ที่ hardcode (เป้าหมายหลักของการ refactor)
5. `ShopUtil` มีวันปิดร้าน hardcode `2030-01-30` และ campaign code (ตรุษจีน/ปีใหม่ลาว `06/06/2025`) — ควรย้ายไป config/tenant setting
6. `getShippingDiscountAttribute()` ของ `ShopOrder` return 0 เสมอ — feature ที่ยังไม่เสร็จ หรือเลิกใช้แล้ว?
7. รวบรวมจุดที่ hardcode ทั้งหมดเป็น backlog เพื่อทยอยทำให้ configurable (ดู "งานที่คาดว่าจะทำต่อ" ด้านล่าง)

### Tenancy / Database
8. ใช้ `stancl/tenancy` แต่เป็น **single-database + tenant_id column** (ไม่มี tenant migration แยก, `database/migrations/tenant` ว่าง) — ยืนยันว่าเป็น design ที่ตั้งใจ ไม่ใช่ multi-database
9. การสร้าง tenant/สินค้า/คูปองใหม่ ตอนนี้ทำผ่าน **seeder เท่านั้น** — เป็นช่องว่างที่ฝั่ง admin ต้องมาเติม

### Testing / คุณภาพ
10. มี `phpunit.xml` แต่ **ไม่มีโฟลเดอร์ `tests/`** เลย (ยังไม่มีชุดเทส) — ควรเริ่มวางโครง test เมื่อเริ่มแก้โค้ด
11. มีเอกสาร/สเปกเดิมจาก outsource dev ไหม (API contract ของ invoice webhook, stock API ฯลฯ) เพื่อ cross-check

---

## งานที่คาดว่าจะทำต่อ (ตามแผนเจ้าของโปรเจค)

1. **ตั้ง git + push ขึ้น GitHub** ของเจ้าของ (ต้นทางเป็น GitLab ของ outsource dev) — เป็น baseline ก่อนแก้
2. **ทำ config ที่ hardcode ให้ปรับได้** (ย้ายไป `config/`, `tenant` setting, หรือ DB)
3. **สร้างฝั่ง admin/backoffice** สำหรับจัดการ brand/tenant, สินค้า, สต็อก, ออเดอร์, คูปอง (ยังไม่มีในโค้ดชุดนี้)
4. ทดสอบครบในเครื่อง/GitHub → ค่อย **merge กับ source ของ outsource dev**

> เอกสารในโฟลเดอร์ `docs/` จะอัปเดตให้สอดคล้องเมื่อมีความคืบหน้า
