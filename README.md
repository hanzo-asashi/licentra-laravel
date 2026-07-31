# Licentra Laravel SDK

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hanzo-asashi/licentra-laravel.svg?style=flat-square)](https://packagist.org/packages/hanzo-asashi/licentra-laravel)
[![Total Downloads](https://img.shields.io/packagist/dt/hanzo-asashi/licentra-laravel.svg?style=flat-square)](https://packagist.org/packages/hanzo-asashi/licentra-laravel)
[![License](https://img.shields.io/github/license/hanzo-asashi/licentra-laravel.svg?style=flat-square)](LICENSE.md)

**Licentra Laravel SDK** adalah Client SDK Resmi berbasis Laravel (kompatibel dengan Laravel 11, 12, 13, PHP 8.4+, Filament 3/4/5, & Livewire 3/4) untuk berkomunikasi dengan **Licentra Server** — Platform Manajemen Lisensi Software Perangkat Lunak Enterprise.

---

## 🚀 Fitur Utama

- 🛡️ **Verifikasi Tanda Tangan Digital RSA 256-bit (`SHA256withRSA`)**: Memverifikasi enkripsi tanda tangan RSA server pada setiap respons `activate` dan `ping` untuk mencegah serangan *Bypassing / DNS Spoofing*.
- 📑 **Validasi Lisensi Offline (`.lic` File & RS256 JWT)**: Membaca, menguji, dan memverifikasi lisensi *air-gapped* offline (file `.lic` JSON bertanda tangan RSA atau token RS256 JWT) tanpa koneksi internet.
- 🔑 **Hardware ID (HWID) Generator & Reset API**: Generator sidik jari perangkat keras (*machine fingerprint*) otomatis dan metode pengajuan reset HWID (`requestHwidReset`).
- 🎛️ **Feature Flags (Entitlements) & Blade Directives**: Manajemen hak akses modul aplikasi (`hasFeature`), middleware (`licentra.feature:modul`), serta Blade Directive `@hasFeature('modul')`.
- 🔔 **Outbound Webhook Receiver & Laravel Events**: Endpoint webhook bawaan (`POST /licentra/webhook`) bertanda tangan RSA yang otomatis membersihkan cache lokal dan men-dispatch **Laravel Events** (`LicenseRevoked`, `LicenseStatusChanged`, `HwidResetApproved`).
- 💻 **Perintah Artisan CLI Lengkap**: CLI bawaan untuk mengelola lisensi dari terminal: `php artisan licentra:status`, `licentra:activate`, `licentra:clear-cache`, `licentra:health`, dan `licentra:update`.
- 👥 **Concurrent Seats Management & Automatic Logout Listener**: Manajemen alokasi kuota user login bersamaan (`checkInSeat`, `keepSeatAlive`, `checkOutSeat`), dilengkapi listener logout otomatis dan middleware heartbeat (`licentra.seat_alive`).
- 🎨 **Integrasi Filament (v3/v4/v5) & Livewire (v3/v4)**: Komponen Blade siap pakai `<x-licentra-laravel::badge />`, `<x-licentra-laravel::banner />`, `<x-licentra-laravel::feature />`, dan `<x-licentra-laravel::activation-form />` dengan dukungan Dark Mode & Filament RenderHooks.
- 🔄 **Automated App Updater Installer (`php artisan licentra:update`)**: Otomatisasi pengunduhan update bertanda tangan digital, *Maintenance Mode*, migrasi database, dan pembersihan cache.
- 🏥 **Health & Network Diagnostic (`php artisan licentra:health`)**: Pengujian diagnostik konektivitas, sertifikasi SSL, Public Key RSA, izin direktori, dan sinkronisasi jam sistem.
- 🔒 **Proteksi Cache Terenkripsi & Anti-Clock-Tampering**: Penyimpanan cache lokal terenkripsi serta proteksi manipulasi jam sistem (clock rewind protection).

---

## 📦 Instalasi

### 1. Instal via Composer

```bash
composer require hanzo-asashi/licentra-laravel
```

Jika repositori berada di VCS privat, tambahkan konfigurasi berikut pada `composer.json` aplikasi Anda:

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

### 2. Publish Konfigurasi & Views (Opsional)

```bash
php artisan vendor:publish --tag="licentra-laravel-config"
php artisan vendor:publish --tag="licentra-laravel-views"
```

---

## ⚙️ Konfigurasi `.env`

Tambahkan variabel berikut pada file `.env` aplikasi Anda:

```env
LICENTRA_URL=https://licentra.test
LICENTRA_LICENSE_KEY=KODE-LISENSI-ANDA
LICENTRA_PRODUCT_SLUG=aquanusa
LICENTRA_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----\n...\n-----END PUBLIC KEY-----"
LICENTRA_CACHE_TTL=3600
LICENTRA_GRACE_PERIOD_DAYS=3
LICENTRA_VERIFY_SSL=false
LICENTRA_WEBHOOK_ENABLED=true
LICENTRA_WEBHOOK_PATH=/licentra/webhook
```

> [!TIP]
> Pada lingkungan pengembangan lokal yang menggunakan **Laravel Herd** / self-signed SSL, atur `LICENTRA_VERIFY_SSL=false` untuk mencegah error cURL 60. Pada server produksi, atur `LICENTRA_VERIFY_SSL=true`.

---

## 💻 Penggunaan

### 1. Aktivasi & Ping Lisensi (Dengan Verification RSA)

```php
use Licentra; // Atau gunakan helper global licentra()

// Aktivasi Lisensi Online (Otomatis mengikat HWID mesin)
$data = Licentra::activate();

// Ping Status Lisensi (Otomatis verifikasi signature & replay-protection nonce)
if (Licentra::ping()) {
    // Lisensi aktif & terverifikasi valid
}
```

### 2. Pengecekan Feature Flags (Entitlements)

```php
if (licentra()->hasFeature('export_excel')) {
    // Akses modul ekspor Excel diizinkan
}
```

### 3. Validasi Lisensi Offline (.lic File & RS256 JWT)

```php
// Simpan dan baca file offline .lic secara lokal
Licentra::saveOfflineLicense($fileContent);
$offlineData = Licentra::loadOfflineLicense();

// Dekode & verifikasi RS256 JWT Token
$jwtPayload = Licentra::verifyJwt($jwtToken);
```

### 4. Pengajuan Reset Hardware ID (HWID)

```php
Licentra::requestHwidReset('Upgrade motherboard dan processor server');
```

### 5. Concurrent Seats (User Login Bersamaan)

```php
// Check-in saat user login
Licentra::checkInSeat(session()->getId(), auth()->user()->email);

// Heartbeat berkala
Licentra::keepSeatAlive(session()->getId());

// Check-out saat user logout (Juga berjalan otomatis via Event Listener Logout)
Licentra::checkOutSeat(session()->getId());
```

### 6. Perintah Artisan CLI

```bash
# Cek status lisensi, validitas, HWID, dan Public Key
php artisan licentra:status

# Jalankan pengujian diagnostik kesehatan koneksi & enkripsi
php artisan licentra:health

# Jalankan pembaruan perangkat lunak otomatis & installer
php artisan licentra:update

# Aktivasi lisensi dari terminal
php artisan licentra:activate AAAA-BBBB-CCCC-DDDD

# Bersihkan cache lisensi lokal
php artisan licentra:clear-cache
```

---

## 🛡️ Route Middleware & Filament Integration

### A. Penggunaan Route Middleware

Daftarkan middleware pada rute aplikasi Anda:

```php
// Memastikan lisensi valid
Route::middleware(['licentra.valid'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

// Memastikan fitur spesifik aktif
Route::middleware(['licentra.feature:export_excel'])->group(function () {
    Route::get('/export', [ExportController::class, 'excel']);
});

// Menjaga sesi concurrent seat tetap aktif
Route::middleware(['auth', 'licentra.seat_alive'])->group(function () {
    Route::get('/app', [AppController::class, 'index']);
});
```

### B. Integrasi Filament (v3/v4/v5) & Livewire (v3/v4)

Tampilkan status lisensi atau banner di Filament Admin Panel (`AdminPanelProvider.php`):

```php
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;

public function panel(Panel $panel): Panel
{
    return $panel
        ->renderHook(
            PanelsRenderHook::TOPBAR_BEFORE,
            fn () => view('licentra-laravel::banner')
        );
}
```

Gunakan Komponen Blade di View Filament/Livewire:

```html
{{-- Status Badge --}}
<x-licentra-laravel::badge />

{{-- Warning Alert Banner --}}
<x-licentra-laravel::banner />

{{-- Form Aktivasi Lisensi --}}
<x-licentra-laravel::activation-form />

{{-- Feature Wrapper --}}
<x-licentra-laravel::feature name="scada_integration">
    <livewire:scada-dashboard />
</x-licentra-laravel::feature>
```

---

## 🧪 Testing & Code Quality

Jalankan test suite Pest, analisis statis PHPStan, dan format kode Pint:

```bash
# Jalankan Pest Tests
composer test

# Jalankan PHPStan Static Analysis (Level 8)
composer analyse

# Format Kode (Laravel Pint)
composer format
```

---

## 📄 Lisensi

Proyek ini berlisensi di bawah [MIT License](LICENSE.md).
