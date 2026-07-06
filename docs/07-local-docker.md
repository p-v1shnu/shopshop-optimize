# 07 — การรันในเครื่องด้วย Docker (Local Dev)

Docker stack สำหรับ dev ในเครื่อง (ไฟล์อยู่ที่ [`docker-compose.yml`](../docker-compose.yml) + [`docker/`](../docker/)) ประกอบด้วย PHP 8.4-fpm + nginx + MySQL 8 + Redis

## Port ที่ใช้ (เลือกให้ไม่ชนกับ Docker project อื่นในเครื่อง)

| Service | Host port | ใช้ทำอะไร |
|---------|-----------|-----------|
| web (nginx) | **8899** | เข้าเว็บ |
| mysql | **33066** | ต่อ DB จากภายนอก (เช่น TablePlus) |
| redis | **63799** | ต่อ Redis |
| node (vite dev) | **5199** | asset dev server (optional) |

ทุก port bind ที่ `127.0.0.1` เท่านั้น (ไม่เปิดออก network)

## ขั้นตอนแรกเริ่ม (one-time)

### 1. เพิ่ม tenant domain ลง hosts file
เพราะระบบแยก tenant ตาม **domain** ต้อง map domain ตัวอย่างไปที่ `127.0.0.1`

แก้ไฟล์ `C:\Windows\System32\drivers\etc\hosts` (เปิด Notepad แบบ **Run as administrator**) เพิ่ม:
```
127.0.0.1  shopshop.test
127.0.0.1  babybright.shopshop.test
127.0.0.1  muanson.shopshop.test
127.0.0.1  gadzila.shopshop.test
```

### 2. รัน stack
```bash
docker compose up -d --build      # ครั้งแรกจะ build image + composer install อัตโนมัติ
```

### 3. ตั้งค่า Laravel (ครั้งแรก)
```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed --force
```
> `.env` สำหรับ Docker ถูกสร้างไว้แล้ว (DB=mysql, cache/queue=redis, APP_URL=http://shopshop.test:8899) — ไฟล์นี้ถูก gitignore ไม่ขึ้น GitHub

### 4. Build assets (frontend)
```bash
docker compose run --rm node sh -c "npm install && npm run build"
```

### 5. เปิดเว็บ
- ร้านตัวอย่าง: http://gadzila.shopshop.test:8899/shop , http://babybright.shopshop.test:8899/shop , http://muanson.shopshop.test:8899/shop
- Central: http://shopshop.test:8899

> ⚠️ **ต้องใส่ `:8899` ต่อท้าย domain เสมอ** — เว็บรันที่ port 8899 ไม่ใช่ 80
> ถ้าพิมพ์แค่ `babybright.shopshop.test` (ไม่มี port) เบราว์เซอร์จะลองต่อ port 80 แล้วได้ **`ERR_CONNECTION_REFUSED`**
> (ถ้าอยากใช้ URL แบบไม่มี port ให้แก้ port mapping ของ service `web` ใน `docker-compose.yml` เป็น `"127.0.0.1:80:80"` โดยต้องมั่นใจว่า port 80 ในเครื่องว่าง)

## การใช้งานประจำวัน

```bash
docker compose up -d                     # เปิด
docker compose down                      # ปิด (ข้อมูล DB คงอยู่ใน volume)
docker compose exec app php artisan ...  # รันคำสั่ง artisan
docker compose exec app php artisan tinker
docker compose logs -f app               # ดู log
docker compose exec app sh               # เข้า shell ใน container

# แก้ CSS/JS แบบ hot reload (optional):
docker compose --profile dev up node     # vite dev server ที่ :5199
```

## หมายเหตุด้านการตั้งค่า (สิ่งที่ปรับ/แก้เพื่อให้รันในเครื่องได้)

การรันในเครื่องเจอปัญหา 4 อย่างและแก้ไว้แล้ว — บันทึกไว้เผื่อเข้าใจที่มา:

1. **`vendor/` และ `node_modules/` อยู่บน named volume** (ไม่ใช่ bind mount)
   เพราะโปรเจคอยู่ในโฟลเดอร์ **OneDrive ของ Windows** การ bind mount เข้า Docker ช้ามาก (PHP ต้อง stat ไฟล์ vendor ~11,000 ไฟล์ = **5 วินาที/request**) พอย้ายไป volume เหลือ ~0.01s ทำให้หน้าเว็บเร็วขึ้นจาก ~9s เหลือ ~0.3s
   > ผลข้างเคียง: ถ้าแก้ `composer.json`/`package.json` ต้องรัน `docker compose exec app composer install` / `docker compose run --rm node npm install` เอง (ไม่ sync จาก host อัตโนมัติ)

2. **`CACHE_STORE=redis`** (ไม่ใช่ `file`) — โค้ดใช้ `Cache::tags()` (HAL + tenancy) ซึ่ง file store ไม่รองรับ → เดิมทำให้หน้า tenant ที่ใช้ HAL error 500

3. **PHP settings** ([`docker/php/php.ini`](../docker/php/php.ini)) — เปิด opcache, memory_limit 512M

4. **php-fpm รันเป็น root** ใน dev image ([`docker/php/Dockerfile`](../docker/php/Dockerfile)) — กันปัญหาสิทธิ์เขียนไฟล์ (`storage/logs`) บน bind mount ของ Windows
   > ⚠️ **เฉพาะ dev เท่านั้น** — production ต้องรันเป็น www-data

### การแก้โค้ด (2 จุด) ที่จำเป็นเพื่อรันบน MySQL/MariaDB เวอร์ชันปัจจุบัน
- [`app/Providers/AppServiceProvider.php`](../app/Providers/AppServiceProvider.php) — บังคับ HTTPS เฉพาะเมื่อ **ไม่ใช่ local** (เดิมบังคับตลอด ทำให้รัน http ในเครื่องพัง)
- [`database/migrations/0001_create_tenants_table.php`](../database/migrations/0001_create_tenants_table.php) — เปลี่ยน `->default('true'/'false')` (string) เป็น boolean `true`/`false` เพราะ MySQL 8 / MariaDB 11 ปฏิเสธ default แบบ string บน column boolean (บั๊กที่เคยรันได้เพราะ DB เดิมเป็นเวอร์ชันเก่ากว่า)

## ต่อ DB ด้วย GUI (เช่น TablePlus / DBeaver)
- Host: `127.0.0.1` · Port: `33066` · User: `shopshop` · Password: `secret` · Database: `shopshop`
