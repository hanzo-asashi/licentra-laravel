# Changelog

All notable changes to `licentra-laravel` will be documented in this file.

## v1.2.0 - Licentra Laravel Client SDK - 2026-08-01

### What's Changed

- 🔐 **RSA-4096 & RS256 JWT License Verification**: Support for online activation/ping and air-gapped offline .lic\ & JWT token verification.
- 🎛️ **Feature Entitlements & Flags**: Middleware \EnsureFeatureIsActive\ and \Licentra::hasFeature()\ to toggle application modules dynamically.
- 👥 **Concurrent Seat Management**: Active session tracking with \CheckOutSeatOnLogout\ listener and \KeepSeatAliveMiddleware.
- 📦 **Automated Software Updater**: \php artisan licentra:update\ command for downloading & applying application releases in maintenance mode.
- 🩺 **Diagnostic Health Checker**: \php artisan licentra:health\ command to diagnose OpenSSL, storage permissions, RSA key status, and API connectivity.
- 🎨 **Blade & Filament UI Components**: Ready-to-use Blade components (<x-licentra-laravel::badge />, <x-licentra-laravel::banner />, <x-licentra-laravel::feature />, <x-licentra-laravel::activation-form />).
- 🛡️ **Security Hardening**: Encrypted local cache storage, anti-clock tampering validation, and deterministic RSA signature verification.
