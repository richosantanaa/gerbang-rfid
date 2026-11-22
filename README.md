# Gerbang RFID — Panduan Menjalankan

Sistem gerbang RFID: Arduino (RC522 + servo) → bridge.py → Laravel 12 API → database MySQL, dengan panel admin Filament 5.

```
Kartu RFID → Arduino → (USB) bridge.py → Laravel /api/gate/check → MySQL
                                              ↑
                          Admin membuka /admin dari browser (PC / HP)
```

## 1. Kebutuhan

| Perangkat lunak | Versi / catatan |
|---|---|
| PHP | 8.4, ekstensi: `pdo_mysql`, `mbstring`, `openssl`, `intl`, `zip`, `fileinfo`, `curl`, `ctype`, `xml` |
| Composer | 2.x |
| MySQL / MariaDB | via XAMPP atau Laragon |
| Python 3 | hanya untuk `bridge.py` (`pip install pyserial requests`) |
| Arduino IDE | untuk upload sketch |

Cek: `php -v`, `composer -V`, `php -m`.

## 2. Menjalankan di komputer baru

Di folder project:

```powershell
# 1. Pasang dependensi (jika folder vendor belum ada / ingin bersih)
composer install

# 2. File konfigurasi
copy .env.example .env
php artisan key:generate
```

Edit `.env`:

```env
APP_NAME="Gerbang RFID"
APP_URL=http://127.0.0.1:8000
APP_LOCALE=id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gerbang_rfid
DB_USERNAME=root
DB_PASSWORD=

# Kunci rahasia untuk perangkat gerbang (buat sendiri, lihat di bawah)
GATE_KEY=
```

Buat `GATE_KEY`:

```powershell
php -r "echo bin2hex(random_bytes(24));"
```

Lalu:

```powershell
# 3. Buat database kosong bernama gerbang_rfid di phpMyAdmin, lalu:
php artisan migrate

# 4. Akun admin untuk login panel
php artisan make:filament-user

# 5. Bersihkan cache, jalankan server
php artisan optimize:clear
php artisan serve
```

Buka `http://127.0.0.1:8000/admin`, login, lalu daftarkan kartu di menu **Pengguna RFID**.

> Alternatif: kalau ingin memakai data yang sudah ada, impor `gerbang_rfid.sql` lewat phpMyAdmin (menggantikan langkah `migrate`).

Tes API tanpa Arduino:

```powershell
Invoke-RestMethod -Method Post -Uri http://127.0.0.1:8000/api/gate/check `
  -Headers @{ "X-Gate-Key" = "ISI_GATE_KEY"; "Accept" = "application/json" } `
  -ContentType "application/json" `
  -Body '{"uid":"92 FD 36 B9"}'
```

## 3. Mengakses dari perangkat lain (satu WiFi / jaringan)

1. **Cari IP komputer server**
   ```powershell
   ipconfig
   ```
   Catat `IPv4 Address` pada adapter WiFi/LAN, misalnya `192.168.1.10`.

2. **Jalankan server agar menerima koneksi dari luar**
   ```powershell
   php artisan serve --host=0.0.0.0 --port=8000
   ```

3. **Buka port di Windows Firewall** (PowerShell sebagai Administrator, sekali saja)
   ```powershell
   New-NetFirewallRule -DisplayName "Laravel 8000" -Direction Inbound -Protocol TCP -LocalPort 8000 -Action Allow
   ```
   Pastikan profil jaringan WiFi adalah **Private**, bukan Public.

4. **Sesuaikan `.env`**
   ```env
   APP_URL=http://192.168.1.10:8000
   ```
   Lalu `php artisan optimize:clear`.

5. **Buka dari HP / laptop lain** (harus di WiFi yang sama):
   `http://192.168.1.10:8000/admin`

6. **Jika `bridge.py` berjalan di komputer lain**, ubah `API_URL` di `bridge.py`:
   ```python
   API_URL = "http://192.168.1.10:8000/api/gate/check"
   ```
   Kalau bridge berjalan di komputer yang sama dengan server, tetap `127.0.0.1`.

Catatan:
- IP bisa berubah saat WiFi tersambung ulang. Atur **IP statis** atau *DHCP reservation* di router supaya tetap.
- Kalau perangkat lain tidak bisa membuka: cek firewall, pastikan satu jaringan yang sama, dan matikan fitur *AP/Client Isolation* di router.
- `php artisan serve` hanya untuk pengembangan. Untuk pemakaian harian, gunakan Apache/Nginx (lihat bagian 5).

## 4. Menjalankan gerbang

1. Upload `gerbang_gate_system.ino` ke Arduino, lalu **tutup Serial Monitor**.
2. Server Laravel berjalan.
3. Jalankan bridge:
   ```powershell
   cd bridge
   pip install pyserial requests
   python bridge.py
   ```
   `bridge.py` v2 mencari Arduino otomatis dan membaca `GATE_KEY` dari `.env`.
4. Tap kartu. Hasil yang diharapkan: `-> DITERIMA (Nama)`, servo membuka, dan baris baru muncul di **Log Akses**.

Satu port serial hanya boleh dibuka satu program. Jangan menjalankan Serial Monitor dan bridge bersamaan.

## 5. Memasang untuk pemakaian sungguhan (production)

`.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-kamu.com
GATE_KEY=<kunci baru, jangan pakai kunci percobaan>
DB_PASSWORD=<password MySQL yang kuat>
```

Perintah:

```powershell
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
php artisan filament:optimize
```

Web server diarahkan ke folder `public/` (bukan ke root project), dan pakai HTTPS.

**Penting — akses panel admin di production.** Filament hanya mengizinkan semua user masuk ke panel saat `APP_ENV=local`. Di luar itu, model `User` harus mengimplementasikan `FilamentUser`, kalau tidak login akan ditolak (403). Ubah `app/Models/User.php`:

```php
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    // ... isi yang sudah ada ...

    public function canAccessPanel(Panel $panel): bool
    {
        return true; // atau batasi, mis. str_ends_with($this->email, '@sekolahmu.sch.id')
    }
}
```

## 6. Keamanan

- Jangan membagikan `.env` (berisi `APP_KEY`, password DB, dan `GATE_KEY`). Kunci yang sudah pernah dibagikan di chat atau ZIP sebaiknya diganti sebelum sistem dipakai sungguhan.
- Ganti `GATE_KEY` di `.env` **dan** di `bridge.py` (kalau diisi manual) secara bersamaan, lalu `php artisan config:clear`.
- Gerbang menolak akses otomatis kalau server tidak menjawab dalam 4 detik. Ini perilaku aman yang disengaja.

## 7. Masalah umum

| Gejala | Penyebab / solusi |
|---|---|
| `401 Unauthorized` dari API | `GATE_KEY` di `.env` dan yang dipakai bridge berbeda. Jalankan `php artisan config:clear` |
| `SQLSTATE ... actively refused` | MySQL belum menyala |
| `Table ... doesn't exist` | Belum `php artisan migrate` |
| `Cannot redeclare class` / `Namespace declaration ... first statement` | File PHP ganda atau ada BOM. Simpan file sebagai UTF-8 tanpa BOM |
| Bridge: `Access is denied` | Port dipakai program lain (Serial Monitor, bridge kedua). Tutup, cabut-colok USB |
| Bridge tidak menerima data | Salah port (banyak port Bluetooth). Gunakan `PORT = "AUTO"` |
| Tidak bisa dibuka dari HP | Firewall, beda jaringan, atau server belum memakai `--host=0.0.0.0` |
| Login `/admin` 403 di production | Implementasikan `FilamentUser` (bagian 5) |
