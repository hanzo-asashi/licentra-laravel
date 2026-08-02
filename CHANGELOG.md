# Changelog

All notable changes to `licentra-laravel` will be documented in this file.

## v1.4.1 - 2026-08-02

### What's Changed

* style: Format SDK Configuration and Test Suite by @hanzo-asashi in https://github.com/hanzo-asashi/licentra-laravel/pull/1

### New Contributors

* @hanzo-asashi made their first contribution in https://github.com/hanzo-asashi/licentra-laravel/pull/1

**Full Changelog**: https://github.com/hanzo-asashi/licentra-laravel/compare/v1.2.2...v1.3.0

**Full Changelog**: https://github.com/hanzo-asashi/licentra-laravel/compare/v1.4.0...v1.4.1

## v1.2.1 - 2026-08-02

**Full Changelog**: https://github.com/hanzo-asashi/licentra-laravel/compare/v1.2.0...v1.2.1

## v1.2.0 - Licentra Laravel Client SDK - 2026-08-01

### What's Changed

- 🔐 **RSA-4096 & RS256 JWT License Verification**: Support for online activation/ping and air-gapped offline .lic\ & JWT token verification.
- 🎛️ **Feature Entitlements & Flags**: Middleware \EnsureFeatureIsActive\ and \Licentra::hasFeature()\ to toggle application modules dynamically.
- 👥 **Concurrent Seat Management**: Active session tracking with \CheckOutSeatOnLogout\ listener and \KeepSeatAliveMiddleware.
- 📦 **Automated Software Updater**: \php artisan licentra:update\ command for downloading & applying application releases in maintenance mode.
- 🩺 **Diagnostic Health Checker**: \php artisan licentra:health\ command to diagnose OpenSSL, storage permissions, RSA key status, and API connectivity.
- 🎨 **Blade & Filament UI Components**: Ready-to-use Blade components (<x-licentra-laravel::badge />, <x-licentra-laravel::banner />, <x-licentra-laravel::feature />, <x-licentra-laravel::activation-form />).
- 🛡️ **Security Hardening**: Encrypted local cache storage, anti-clock tampering validation, and deterministic RSA signature verification.
