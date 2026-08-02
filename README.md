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
- 📊 **Limit & Quota Management**: Manajemen batas kuota lisensi (`getLimit`, `hasReachedLimit`) dengan dukungan unlimited (`null` / `-1`), fallback ke `default_limits` config, dan sinkronisasi otomatis dari respons `activate` / `ping`.
- 🔔 **Outbound Webhook Receiver & Laravel Events**: Endpoint webhook bawaan (`POST /licentra/webhook`) bertanda tangan RSA yang otomatis membersihkan cache lokal dan men-dispatch **Laravel Events** (`LicenseRevoked`, `LicenseStatusChanged`, `HwidResetApproved`).
- 💻 **Perintah Artisan CLI Lengkap**: CLI bawaan untuk mengelola lisensi dari terminal: `licentra:status`, `licentra:activate`, `licentra:clear-cache`, `licentra:health`, `licentra:update`, `licentra:sync`, dan `licentra:install-license`.
- 👥 **Concurrent Seats Management & Automatic Logout Listener**: Manajemen alokasi kuota user login bersamaan (`checkInSeat`, `keepSeatAlive`, `checkOutSeat`), dilengkapi listener logout otomatis dan middleware heartbeat (`licentra.seat_alive`).
- 🏢 **Multi-Tenant Support**: Mendukung arsitektur multi-tenant dengan metode `forTenant()`, `forLicenseKey()`, dan `resolveLicenseKeyUsing()` untuk resolusi license key dinamis per tenant.
- 🎨 **Integrasi Filament (v3/v4/v5) & Livewire (v3/v4)**: Komponen Blade siap pakai `<x-licentra-laravel::badge />`, `<x-licentra-laravel::banner />`, `<x-licentra-laravel::feature />`, dan `<x-licentra-laravel::activation-form />` dengan dukungan Dark Mode & Filament RenderHooks.
- 🔄 **Automated App Updater Installer (`php artisan licentra:update`)**: Otomatisasi pengunduhan update bertanda tangan digital, *Maintenance Mode*, migrasi database, dan pembersihan cache.
- 🏥 **Health & Network Diagnostic (`php artisan licentra:health`)**: Pengujian diagnostik konektivitas, sertifikasi SSL, Public Key RSA, izin direktori, dan sinkronisasi jam sistem.
- 🔒 **Proteksi Cache Terenkripsi & Anti-Clock-Tampering**: Penyimpanan cache lokal terenkripsi serta proteksi manipulasi jam sistem (clock rewind protection) dengan toleransi drift NTP yang dapat dikonfigurasi.
- 🚫 **Certificate Revocation List (CRL)**: Pengecekan pencabutan lisensi via CRL bertanda tangan RSA (`fetchCrl`, `isRevoked`) dengan fallback cache offline.
- 🔁 **Background Sync (`php artisan licentra:sync`)**: Sinkronisasi status lisensi & CRL secara berkala di background melalui Laravel Scheduler.
- 📥 **Offline License Installer (`php artisan licentra:install-license`)**: Instalasi dan verifikasi file lisensi offline `.lic` via Artisan CLI dengan validasi tanda tangan RSA.

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
    "hanzo-asashi/licentra-laravel": "^1.4"
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
LICENTRA_CLOCK_DRIFT_TOLERANCE=300
LICENTRA_VERIFY_SSL=true
LICENTRA_WEBHOOK_ENABLED=true
LICENTRA_WEBHOOK_PATH=/licentra/webhook
```

### Variabel Konfigurasi

| Variabel | Default | Deskripsi |
|---|---|---|
| `LICENTRA_URL` | `https://licentra.test` | Base URL Licentra Server |
| `LICENTRA_LICENSE_KEY` | — | Kode lisensi aplikasi |
| `LICENTRA_PRODUCT_SLUG` | `aquanusa` | Slug produk yang didaftarkan di server |
| `LICENTRA_PUBLIC_KEY` | — | RSA Public Key untuk verifikasi tanda tangan |
| `LICENTRA_CACHE_TTL` | `3600` | TTL cache status lisensi (detik) |
| `LICENTRA_GRACE_PERIOD_DAYS` | `3` | Jumlah hari operasi offline jika ping gagal |
| `LICENTRA_CLOCK_DRIFT_TOLERANCE` | `300` | Toleransi drift jam mundur dalam detik (NTP adjustment) |
| `LICENTRA_VERIFY_SSL` | `true` | Verifikasi sertifikat SSL pada koneksi HTTP |
| `LICENTRA_WEBHOOK_ENABLED` | `true` | Aktifkan endpoint webhook receiver |
| `LICENTRA_WEBHOOK_PATH` | `/licentra/webhook` | Path custom untuk webhook endpoint |

> [!TIP]
> Pada lingkungan pengembangan lokal yang menggunakan **Laravel Herd** / self-signed SSL, atur `LICENTRA_VERIFY_SSL=false` untuk mencegah error cURL 60. Pada server produksi, biarkan default `true`.

> [!IMPORTANT]
> Mulai versi `v1.4.0`, default `LICENTRA_VERIFY_SSL` adalah `true` (sebelumnya `false`). Pastikan server produksi Anda memiliki sertifikat SSL yang valid.

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

### 3. Limit & Quota Management

```php
// Ambil nilai limit tertentu (return mixed, null = unlimited)
$maxUsers = licentra()->getLimit('max_users');           // e.g. 50
$maxStorage = licentra()->getLimit('max_storage_gb', 10); // default 10 jika tidak ada

// Cek apakah penggunaan sudah mencapai/melewati batas
$currentUserCount = User::count();

if (licentra()->hasReachedLimit('max_users', $currentUserCount)) {
    abort(403, 'Kuota pengguna telah tercapai. Upgrade lisensi Anda.');
}

// Limit bernilai null atau -1 dianggap unlimited (hasReachedLimit selalu return false)
```

Konfigurasi fallback default limit di `config/licentra-laravel.php`:

```php
'default_limits' => [
    'max_users' => 5,
    'max_storage_gb' => 1,
],
```

### 4. Validasi Lisensi Offline (.lic File & RS256 JWT)

```php
// Simpan dan baca file offline .lic secara lokal
Licentra::saveOfflineLicense($fileContent);
$offlineData = Licentra::loadOfflineLicense();

// Dekode & verifikasi RS256 JWT Token
$jwtPayload = Licentra::verifyJwt($jwtToken);
```

### 5. Pengajuan Reset Hardware ID (HWID)

```php
Licentra::requestHwidReset('Upgrade motherboard dan processor server');
```

### 6. Concurrent Seats (User Login Bersamaan)

```php
// Check-in saat user login
Licentra::checkInSeat(session()->getId(), auth()->user()->email);

// Heartbeat berkala
Licentra::keepSeatAlive(session()->getId());

// Check-out saat user logout (Juga berjalan otomatis via Event Listener Logout)
Licentra::checkOutSeat(session()->getId());
```

### 7. Certificate Revocation List (CRL)

```php
// Fetch CRL bertanda tangan RSA dari server
$crlData = Licentra::fetchCrl();

// Cek apakah lisensi saat ini dicabut/suspended
if (Licentra::isRevoked()) {
    abort(403, 'Lisensi telah dicabut oleh administrator.');
}

// Cek lisensi spesifik terhadap CRL
if (Licentra::isRevoked('XXXX-YYYY-ZZZZ-1234')) {
    // Lisensi tersebut dicabut
}
```

### 8. Multi-Tenant Support

Untuk aplikasi multi-tenant, SDK mendukung resolusi license key dinamis per tenant:

```php
// Opsi A: Scoped instance untuk tenant tertentu
$tenant = Tenant::current();
$licentra = licentra()->forTenant($tenant, 'license_key');

if ($licentra->ping()) {
    $maxUsers = $licentra->getLimit('max_users');
}

// Opsi B: Scoped instance untuk license key tertentu
$licentra = licentra()->forLicenseKey('AAAA-BBBB-CCCC-DDDD');
$data = $licentra->activate();

// Opsi C: Register global resolver (e.g. di AppServiceProvider atau Middleware)
use Licentra\LicentraLaravel\LicentraLaravel;

LicentraLaravel::resolveLicenseKeyUsing(function () {
    return Tenant::current()->license_key;
});

// Setelah resolver didaftarkan, semua panggilan otomatis menggunakan license key tenant aktif
Licentra::ping();
Licentra::hasFeature('export_excel');
```

### 9. Perintah Artisan CLI

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

# Sinkronisasi status lisensi & CRL di background
php artisan licentra:sync
php artisan licentra:sync --force   # Force bypass ping cache

# Install file lisensi offline (.lic) dengan verifikasi RSA
php artisan licentra:install-license /path/to/license.lic
php artisan licentra:install-license /path/to/license.lic --path=/custom/save/path.lic
```

### 10. Background Sync via Laravel Scheduler

Daftarkan perintah `licentra:sync` di `routes/console.php` atau `app/Console/Kernel.php` untuk sinkronisasi otomatis:

```php
// routes/console.php (Laravel 11+)
use Illuminate\Support\Facades\Schedule;

Schedule::command('licentra:sync')->hourly();
```

```php
// app/Console/Kernel.php (Laravel 10 dan sebelumnya)
protected function schedule(Schedule $schedule): void
{
    $schedule->command('licentra:sync')->hourly();
}
```

> [!NOTE]
> Command `licentra:sync` akan melakukan ping ke server Licentra dan memperbarui CRL (Certificate Revocation List) secara berkala. Gunakan flag `--force` untuk bypass cache dan memaksa sinkronisasi langsung.

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

## 📋 Changelog

### v1.4.1
- Fix: Resolusi tipe PHPStan pada `LicentraInstallLicenseCommand`.

### v1.4.0
- ✨ **Artisan `licentra:sync`** — Background sync license status & CRL via Laravel Scheduler.
- ✨ **Artisan `licentra:install-license`** — Verifikasi RSA signature dan install file `.lic` offline via CLI.
- ✨ **Multi-Tenant Support** — `forTenant()`, `forLicenseKey()`, `resolveLicenseKeyUsing()` untuk resolusi license key dinamis.

### v1.3.0
- ✨ **SSL Verification Default** — `verify_ssl` diubah ke `true` secara default untuk keamanan produksi.
- ✨ **Clock Drift Tolerance** — Toleransi NTP clock drift (default 300 detik) pada `isClockTampered()` untuk mencegah false positive.
- ✨ **Limit & Quota Management** — `getLimit()`, `hasReachedLimit()` dengan dukungan unlimited, fallback default, dan caching otomatis.
- ✨ **CRL (Certificate Revocation List)** — `fetchCrl()`, `isRevoked()` dengan verifikasi RSA dan fallback cache offline.

### v1.2.0
- ✨ CRL support, webhook events, health diagnostic command.

### v1.1.0
- ✨ Concurrent seats management, Filament/Livewire integration, auto-updater.

### v1.0.0
- 🎉 Initial release — Aktivasi, ping, offline license, HWID, feature flags, encrypted cache.

---

## 📄 Lisensi

Proyek ini berlisensi di bawah [MIT License](LICENSE.md).
