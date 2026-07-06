# 03 — Frontend & Shop Flow

Frontend ทั้งหมดสร้างด้วย **Livewire 4** components (`app/Livewire/Frontend/`) + Blade views (`resources/views/frontend/`) ไม่มี SPA แยก — Livewire จัดการ interactivity ฝั่ง server พร้อม Vue 2 เสริมบางจุด

## 1. Flow การช้อปปิ้งแบบ end-to-end

```
[Guest] เข้าเว็บ
   └─ ล็อกอิน OtpLoginModal (เบอร์ → OTP → สร้าง/เข้าสู่ระบบ)
        └─ ถ้า profile ไม่ครบ → CheckProfile บังคับไป ProfileEditPage

[Browse]  ShopProductsPage (หน้าแรก) / ShopSearchPage (ค้นหา + เก็บประวัติ)
   └─ ShopProductDetailPage (รายละเอียด + สินค้าแนะนำ)
        └─ addToCart() → เก็บลง session (cartProducts) → dispatch 'shop.refreshCart'

[Cart]    ShopCartPage
   ├─ ShopCartProduct (เพิ่ม/ลด/ลบ พร้อม validate สต็อก)
   ├─ ShopCouponInput (ถ้า tenant เปิด coupon)
   └─ สรุปยอด: cartAmount + shipping - coupon = cartNetAmount
        └─ "ไปหน้าจัดส่ง"

[Shipping/Payment]  ShopShippingPage
   ├─ เลือกช่องทางจ่าย: BCEL / JDB / LaoQR
   ├─ เลือกขนส่ง:
   │    ├─ HAL → HalBranchSelector (จังหวัด→อำเภอ→สาขา, ดึงจาก HAL API)
   │    ├─ Seller → SellerAddressSelector (จังหวัด→อำเภอ→หมู่บ้าน)
   │    └─ None → ใช้ที่อยู่ user (แบบ pickup)
   └─ createOrder()  ← หัวใจของระบบ (ดูข้อ 2)

[Payment]  ShopCheckoutPage (แสดง QR ตามช่องทาง)
   └─ ฟัง OrderPaid ผ่าน Laravel Echo (realtime)
        └─ จ่ายสำเร็จ → เคลียร์ session → ยิง GTM purchase + FB Pixel → modal สำเร็จ

[Track]   ShopOrdersPage (แท็บ pending/shipping/completed)
   └─ ShopOrderDetailPage → HalShipmentTracking (สถานะพัสดุ realtime จาก HAL)

[Profile] ProfilePage (นับออเดอร์ตามสถานะ) / ProfileEditPage (แก้ข้อมูล + รับ term)
```

## 2. `ShopShippingPage::createOrder()` — logic สำคัญที่สุด

สร้างออเดอร์ภายใน DB transaction พร้อม row-lock กัน race condition:

1. `DB::beginTransaction()`
2. Validate: shop เปิดอยู่, สินค้ายัง active + สต็อกพอ (**lock แถวสินค้า**), คูปองยัง valid + ไม่เกิน daily limit (**lock แถวคูปอง**), ยอดขั้นต่ำผ่าน
3. ถ้าใช้ HAL → เรียก HAL API `calculate-freight` + `create-pre-order` → ได้ tracking number + shipping detail
4. สร้าง `ShopOrder` (id = nanoid 16 ตัว, payment หมดอายุใน **5 นาที**) + `ShopOrderDetail` แต่ละรายการ
5. ตัดสต็อก (บันทึก `ShopProductStock`)
6. ถ้ามีคูปอง → สร้าง `ShopOrderCoupon` + ลด `available_quantity` ของคูปอง
7. สร้าง QR:
   - BCEL → EMV string (`BCELUtil`) เก็บใน `generate_qr_response`
   - JDB / LaoQR → เรียก API (`JDBUtil`) เก็บ response
8. `DB::commit()` → redirect ไป `ShopCheckoutPage`

## 3. Livewire Components Reference

### Product / Search
| Component | หน้าที่ | method สำคัญ |
|-----------|---------|--------------|
| `ShopProductsPage` | หน้าแรก แสดงสินค้า active ทั้งหมด (เรียงตาม sort_no) | `mount()` |
| `ShopProductList` | grid สินค้า (reusable) + ปุ่มใส่ตะกร้า | `addToCart()` — validate auth/shop/stock แล้ว dispatch `shop.refreshCart` |
| `ShopProductDetailPage` | รายละเอียดสินค้า + สินค้าแนะนำ 2 ชิ้นสุ่ม | `mount(productId)`, `addToCart()` |
| `ShopSearchPage` | ค้นหา + ประวัติการค้นหา (บันทึก `ShopUserSearch`, เพิ่ม `total_search`) | `searchProducts()`, `clearUserSearches()` |

### Cart
| Component | หน้าที่ |
|-----------|---------|
| `ShopCartIcon` | badge จำนวนในตะกร้าบน navbar (ฟัง `shop.refreshCart`) |
| `ShopCartPage` | หน้าตะกร้าเต็ม + ปุ่มไปหน้าจัดส่ง |
| `ShopCartProduct` | การ์ดสินค้าในตะกร้า (เพิ่ม/ลด/ลบ) — ใช้ทั้งในตะกร้าและหน้า review |
| `ShopCartSummaryButton` | ปุ่มสรุปยอด (footer) |
| **`Concerns/ShopCartTrait`** | **trait รวม state ตะกร้า** ใช้ร่วมหลาย component — มี computed: `cartQuantity`, `cartAmount`, `cartShippingRule` (จับคู่ตามวันที่+ยอด), `cartShippingAmount`, `cartCouponAmount`, `cartNetAmount`; method `restoreProductsFromSession()` / `restoreCouponFromSession()` (re-validate ทุกครั้ง) |
| `ShopCouponInput` | กรอก/ใช้/ลบคูปอง (3 state: initial/input/applied) |

### Checkout / Shipping
| Component | หน้าที่ |
|-----------|---------|
| `ShopShippingPage` | เลือก payment + ขนส่ง + `createOrder()` (ดูข้อ 2) |
| `ShopCheckoutPage` | แสดง QR + ฟัง `OrderPaid` (Echo) → success modal, ยิง analytics |
| `HalBranchSelector` | dropdown จังหวัด→อำเภอ→สาขา HAL (cache 60 นาที, lazy load) |
| `SellerAddressSelector` | จังหวัด→อำเภอ→หมู่บ้าน (ขนส่งแบบ seller) |
| `HalShipmentTracking` | ดึงสถานะพัสดุจาก HAL (cache 5 นาที, lazy load) |

### Order / Profile / Modal
| Component | หน้าที่ |
|-----------|---------|
| `ShopOrdersPage` | รายการออเดอร์ แท็บ pending/shipping/completed (query `payment_status='paid'` + `shipping_status`) |
| `ShopOrderDetailPage` | รายละเอียดออเดอร์ + tracking + validate ว่าเป็นของ user นั้น |
| `ProfilePage` | dashboard นับออเดอร์ตามสถานะ |
| `ProfileEditPage` | ฟอร์มโปรไฟล์ (ชื่อ/วันเกิด/เพศ/ที่อยู่/รับ term) → ยิง GTM `user-registered`/`user-updated` |
| `OtpLoginModal` | ล็อกอินเบอร์ + OTP (modal) |
| `AlertModal` | dialog แจ้งเตือนกลาง (error/success/info) เรียกผ่าน `dispatch('openModal','alert-modal',[...])` |
| `PopupBanner` / `PopupBannerModal` | banner โปรโมชัน (แสดงทุก 10 นาที, cache รายคน/session) |
| `AcceptTermModal`, `ShopNoticeModal` | modal รับเงื่อนไข / ประกาศร้าน |

## 4. Blade layout & views

- Layout หลัก: [`resources/views/frontend/livewire/layout.blade.php`](../resources/views/frontend/livewire/layout.blade.php)
  - `<head>`: meta/OG, Vite CSS, Livewire styles, custom `head_html` ของ tenant, Siema carousel
  - `<body>`: navbar (ถ้า `showNavbar`), slot, footer, modals
  - เช็ค `?action=login` → เปิด OTP modal อัตโนมัติ
  - option `footerJs` โหลด `echo.js` (realtime)
- หน้า checkout แยก 3 ไฟล์ตาม gateway: `shop-checkout-bcel-page`, `shop-checkout-jdb-page`, `shop-checkout-laoqr-page`
- Components ที่ใช้ซ้ำ: `cart-footer-summary`, `shop-shipping-detail`, `shop-order-detail-product`, `navbar`, `footer`, `purchase-datalayer` (GTM)

## 5. Frontend JS ([`resources/js/`](../resources/js/))

| ไฟล์ | หน้าที่ |
|------|---------|
| `app.js` | import รูปภาพทั้งหมดสำหรับ bundle |
| `echo.js` | ตั้งค่า Laravel Echo (ฟัง `OrderPaid`) |
| `livewireVue.js` | hook เชื่อม Livewire กับ Vue 2 |
| `util.js` | helper (CSRF token, สร้าง image URL) — `appMixin` |
| `qrUtil.js` | utility เกี่ยวกับ QR |

## 6. Session keys & Events

**Session:**
```
cartProducts          → [ { product, quantity }, ... ]
cartCoupon            → { id, code, type, amount, min_order_amount, user_daily_limit, ... }
paymentChannel        → 'bcel' | 'jdb' | 'laoqr' | ''
sellerShippingAddress → { provinceCode, district, village }
```

**Livewire events:**
`shop.refreshCart`, `openModal`/`closeModal`, `halBranchSelected`, `sellerShippingAddressSelected`, `acceptTerm`, `order-paid`/`user-registered`/`user-updated` (→ GTM), `echo-private:orders.{id},OrderPaid` (realtime)

## 7. Validation & Analytics

- **Cart** (`ShopUtil::shopValidation()`): shop เปิด, ไม่ปิด (มี cutoff hardcode `2030-01-30`), ตะกร้าไม่ว่าง, สินค้ายัง active
- **Coupon**: code มีจริง+active, อยู่ในช่วงเวลา, มี quantity, เป็นของ user (ถ้า private), amount ถูกต้อง, ถึงยอดขั้นต่ำ, ไม่เกิน daily limit (row-lock)
- **Analytics**: GTM (`order-paid`, `user-registered`, `user-updated`) + Facebook Pixel (Purchase, เฉพาะ production)
