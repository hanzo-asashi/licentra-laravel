# Licentra Laravel SDK Roadmap & Task List

## 1. Security & Stability Fixes
- [x] **Fix Default SSL Verification**: Change default `'verify_ssl'` in `config/licentra-laravel.php` to `true` (`env('LICENTRA_VERIFY_SSL', true)`) to enforce secure-by-default SSL verification.
- [x] **Clock Drift Tolerance in `isClockTampered`**: Add NTP clock drift tolerance (e.g., 300 seconds / 5 minutes) in `LicentraLaravel::isClockTampered()` to prevent false positives when system clocks resync.

## 2. Feature Enhancements
- [x] **Limit & Quota Management**: Add `getLimit(string $key, $default = null)` and `hasReachedLimit(string $key, int $currentUsage)` to support tiered feature limits & usage quotas.
- [x] **Background License Sync Command (`licentra:sync`)**: Sediakan Artisan Command `LicentraSyncCommand` (`licentra:sync`) untuk memperbarui status ping & CRL secara background via Laravel Scheduler.
- [x] **CLI Offline License Installer (`licentra:install-license`)**: Add `LicentraInstallLicenseCommand` to validate RSA signature and save offline `.lic` files via Artisan CLI.
- [x] **Multi-Tenant License Resolver**: Support multi-tenancy by allowing dynamic tenant license key resolution and isolated cache keys per tenant.
