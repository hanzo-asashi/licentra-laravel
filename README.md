# Licentra Laravel SDK

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hanzo-asashi/licentra-laravel.svg?style=flat-square)](https://packagist.org/packages/hanzo-asashi/licentra-laravel)
[![Total Downloads](https://img.shields.io/packagist/dt/hanzo-asashi/licentra-laravel.svg?style=flat-square)](https://packagist.org/packages/hanzo-asashi/licentra-laravel)

SDK Client Resmi Laravel untuk **Licentra** — Platform Manajemen Lisensi Perangkat Lunak Enterprise.

---

## 🚀 Fitur Utama

- 🔐 **Aktivasi & Ping Lisensi Online**: Pengecekan status lisensi secara langsung dengan proteksi *anti-replay attack* (nonce & timestamp).
- ⚡ **Built-in Intelligent Caching & Grace Period**: Menyimpan status ping di cache lokal untuk performa tinggi, serta mendukung *Offline Grace Period* jika server Licentra tidak dapat dijangkau sementara.
- 🎛️ **Feature Flags (Entitlements)**: Mengontrol akses modul aplikasi (contoh: `billing`, `scada`, `iot_metering`) secara dinamis berdasarkan paket lisensi.
- 👥 **Manajemen Concurrent Seats**: Pengecekan alokasi kuota user login bersamaan (`check-in` & `check-out`).
- 📦 **Auto-Updater API**: Pengecekan rilis versi terbaru dan patch aplikasi secara otomatis.
- 🛡️ **Middleware & Facade Bawaan**: Siap pakai via Facade `Licentra::ping()` atau Middleware `EnsureLicenseIsValid`.

---

## 📦 Instalasi

### 1. Instal via Composer
Jika package berada di GitHub repository privat Anda, tambahkan ke `composer.json` aplikasi klien (contoh: AQUANUSA):

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/hanzo-asashi/licentra-laravel.git"
    }
],
"require": {
    "hanzo-asashi/licentra-laravel": "^1.0"
}
```

Jalankan perintah:
```bash
composer update hanzo-asashi/licentra-laravel
```

### 2. Publish Konfigurasi (Opsional)
```bash
php artisan vendor:publish --tag="licentra-laravel-config"
```

---

## ⚙️ Konfigurasi `.env`

Tambahkan variabel berikut pada file `.env` aplikasi klien Anda:

```env
LICENTRA_URL=https://licentra.test
LICENTRA_LICENSE_KEY=KODE-LISENSI-ANDA
LICENTRA_PRODUCT_SLUG=aquanusa
LICENTRA_CACHE_TTL=3600
LICENTRA_GRACE_PERIOD_DAYS=3
```

---

## 💻 Penggunaan

### 1. Ping Status Lisensi
```php
use Licentra;

if (Licentra::ping()) {
    // Lisensi aktif & valid
}
```

### 2. Pengecekan Feature Flags (Entitlements)
```php
use Licentra;

if (Licentra::hasFeature('scada')) {
    // Tampilkan / izinkan akses modul SCADA
}
```

### 3. Seat Check-in & Check-out (Concurrent Users)
```php
use Licentra;

// Check-in saat user login
$seat = Licentra::checkInSeat(session()->getId(), auth()->user()->email);

// Check-out saat user logout
Licentra::checkOutSeat(session()->getId());
```

### 4. Check Updates Versi Aplikasi
```php
use Licentra;

$update = Licentra::checkForUpdates(currentVersion: '1.0.0');

if ($update['update_available']) {
    // Ada versi baru: $update['latest_version']
}
```

### 5. Penggunaan Route Middleware
Tambahkan middleware `Licentra\LicentraLaravel\Middleware\EnsureLicenseIsValid` pada rute aplikasi Anda:

```php
Route::middleware([EnsureLicenseIsValid::class])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});
```

---

## 🧪 Testing

```bash
composer test
```

## 📄 Lisensi

Licensed under the [MIT License](LICENSE.md).
