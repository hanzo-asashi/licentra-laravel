<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Licentra\LicentraLaravel\Events\LicenseRevoked;
use Licentra\LicentraLaravel\Facades\LicentraLaravel;
use Licentra\LicentraLaravel\Support\CryptoVerifier;
use Licentra\LicentraLaravel\Support\HwidGenerator;

beforeEach(function () {
    $this->baseUrl = 'https://licentra.test';
    $this->licenseKey = 'AAAA-BBBB-CCCC-DDDD';

    $config = [
        'digest_alg' => 'sha256',
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ];

    $userProfile = getenv('USERPROFILE') ?: getenv('HOME');
    $candidates = array_filter([
        getenv('OPENSSL_CONF'),
        $userProfile ? $userProfile.'/.config/herd/openssl.cnf' : null,
        'C:/Users/hanse/.config/herd/openssl.cnf',
        'C:/Program Files/Herd/resources/app.asar.unpacked/resources/openssl.cnf',
    ]);
    foreach ($candidates as $path) {
        if ($path && file_exists($path)) {
            $config['config'] = $path;
            break;
        }
    }

    // Generate test RSA Key pair
    $res = openssl_pkey_new($config);
    if (! $res) {
        throw new RuntimeException('Failed to generate RSA key for test: '.openssl_error_string());
    }

    $privateKey = null;
    openssl_pkey_export($res, $privateKey, null, $config);
    $this->privateKey = $privateKey;

    $pubDetails = openssl_pkey_get_details($res);
    $this->publicKey = $pubDetails['key'];

    config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    config()->set('licentra-laravel.url', $this->baseUrl);
    config()->set('licentra-laravel.license_key', $this->licenseKey);
    config()->set('licentra-laravel.public_key', $this->publicKey);
    config()->set('licentra-laravel.verify_ssl', false);
});

it('generates a valid 64-character SHA256 HWID', function () {
    $hwid = HwidGenerator::generate();
    expect($hwid)->toBeString()->toHaveLength(64);
});

it('verifies RSA signature correctly', function () {
    $payload = ['status' => 'valid', 'license_key' => $this->licenseKey];
    $json = json_encode($payload);

    openssl_sign($json, $rawSig, $this->privateKey, OPENSSL_ALGO_SHA256);
    $signature = base64_encode($rawSig);

    $isValid = CryptoVerifier::verifySignature($json, $signature, $this->publicKey);
    expect($isValid)->toBeTrue();
});

it('verifies offline license file (.lic format)', function () {
    $payload = [
        'license_key' => $this->licenseKey,
        'product_id' => 1,
        'valid_until' => date('Y-m-d', strtotime('+30 days')),
        'features' => ['feature_a', 'feature_b'],
    ];

    $payloadString = CryptoVerifier::deterministicJsonEncode($payload);
    openssl_sign($payloadString, $rawSig, $this->privateKey, OPENSSL_ALGO_SHA256);

    $licContent = json_encode([
        'payload' => $payload,
        'signature' => base64_encode($rawSig),
    ]);

    $verified = LicentraLaravel::verifyOfflineLicense($licContent);
    expect($verified['license_key'])->toBe($this->licenseKey);
    expect($verified['features'])->toContain('feature_a');
});

it('verifies RS256 JWT tokens', function () {
    $header = ['alg' => 'RS256', 'typ' => 'JWT'];
    $payload = [
        'license_key' => $this->licenseKey,
        'exp' => time() + 3600,
        'features' => ['jwt_feature'],
    ];

    $b64Header = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
    $b64Payload = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');

    $sigInput = $b64Header.'.'.$b64Payload;
    openssl_sign($sigInput, $rawSig, $this->privateKey, OPENSSL_ALGO_SHA256);
    $b64Sig = rtrim(strtr(base64_encode($rawSig), '+/', '-_'), '=');

    $jwt = $sigInput.'.'.$b64Sig;

    $decoded = LicentraLaravel::verifyJwt($jwt);
    expect($decoded['license_key'])->toBe($this->licenseKey);
    expect($decoded['features'])->toContain('jwt_feature');
});

it('activates license online with RSA signature verification', function () {
    $data = [
        'status' => 'Active',
        'license_key' => $this->licenseKey,
        'features' => ['analytics', 'exports'],
    ];

    $payloadString = CryptoVerifier::deterministicJsonEncode($data);
    openssl_sign($payloadString, $rawSig, $this->privateKey, OPENSSL_ALGO_SHA256);

    Http::fake([
        'https://licentra.test/api/license/activate' => Http::response([
            'message' => 'License activated successfully',
            'data' => $data,
            'signature' => base64_encode($rawSig),
        ], 200),
    ]);

    $result = LicentraLaravel::activate();
    expect($result['status'])->toBe('Active');
    expect(LicentraLaravel::hasFeature('analytics'))->toBeTrue();
});

it('performs ping with RSA verification', function () {
    $data = [
        'status' => 'valid',
        'license_key' => $this->licenseKey,
        'features' => ['premium'],
    ];

    $payloadString = CryptoVerifier::deterministicJsonEncode($data);
    openssl_sign($payloadString, $rawSig, $this->privateKey, OPENSSL_ALGO_SHA256);

    Http::fake([
        'https://licentra.test/api/license/ping' => Http::response([
            'data' => $data,
            'signature' => base64_encode($rawSig),
        ], 200),
    ]);

    $pingResult = LicentraLaravel::ping();
    expect($pingResult)->toBeTrue();
});

it('sends HWID reset request', function () {
    Http::fake([
        'https://licentra.test/api/license/hwid-reset' => Http::response([
            'message' => 'HWID reset request submitted successfully',
            'data' => ['status' => 'Pending'],
        ], 200),
    ]);

    $res = LicentraLaravel::requestHwidReset('Server hardware upgrade');
    expect($res['data']['status'])->toBe('Pending');
});

it('checks in seat and keeps seat alive', function () {
    Http::fake([
        'https://licentra.test/api/license/seat/check-in' => Http::response([
            'status' => 'checked_in',
            'session_id' => 'sess_123',
            'active_seats' => 1,
            'max_seats' => 5,
        ], 200),
    ]);

    $res = LicentraLaravel::keepSeatAlive('sess_123', 'user@example.com');
    expect($res['status'])->toBe('checked_in');
    expect($res['active_seats'])->toBe(1);
});

it('provides global licentra() helper function and clears cache', function () {
    expect(licentra())->toBeInstanceOf(Licentra\LicentraLaravel\LicentraLaravel::class);

    LicentraLaravel::clearCache();
    expect(true)->toBeTrue();
});

it('handles licentra webhook post and dispatches events', function () {
    Event::fake();

    $payload = ['license_key' => $this->licenseKey, 'status' => 'Revoked'];
    $json = CryptoVerifier::deterministicJsonEncode($payload);
    openssl_sign($json, $rawSig, $this->privateKey, OPENSSL_ALGO_SHA256);

    $response = $this->postJson('/licentra/webhook', [
        'event' => 'license.revoked',
        'data' => $payload,
        'signature' => base64_encode($rawSig),
    ]);

    $response->assertStatus(200);
    Event::assertDispatched(LicenseRevoked::class);
});

it('runs licentra artisan commands successfully', function () {
    $this->artisan('licentra:status')->assertExitCode(0);
    $this->artisan('licentra:clear-cache')->assertExitCode(0);
});

it('saves and loads offline license file', function () {
    $payload = [
        'license_key' => $this->licenseKey,
        'valid_until' => date('Y-m-d', strtotime('+30 days')),
    ];
    $jsonPayload = CryptoVerifier::deterministicJsonEncode($payload);
    openssl_sign($jsonPayload, $rawSig, $this->privateKey, OPENSSL_ALGO_SHA256);

    $content = json_encode([
        'payload' => $payload,
        'signature' => base64_encode($rawSig),
    ]);

    $tmpPath = sys_get_temp_dir().'/test_offline.lic';
    LicentraLaravel::saveOfflineLicense($content, $tmpPath);

    $loaded = LicentraLaravel::loadOfflineLicense($tmpPath);
    expect($loaded['license_key'])->toBe($this->licenseKey);

    @unlink($tmpPath);
});

it('renders blade components for Filament and Livewire', function () {
    Http::fake([
        'https://licentra.test/api/license/ping' => Http::response([
            'data' => ['status' => 'valid', 'license_key' => $this->licenseKey],
        ], 200),
    ]);

    $view = $this->blade('<x-licentra-laravel::badge />');
    $view->assertSee('Active');
});

it('runs health check and update commands successfully', function () {
    Http::fake([
        'https://licentra.test/api/license/public-key' => Http::response([
            'public_key' => $this->publicKey,
        ], 200),
        'https://licentra.test/api/releases/check*' => Http::response([
            'update_available' => false,
            'latest_version' => '1.0.0',
        ], 200),
    ]);

    $this->artisan('licentra:health')->assertExitCode(0);
    $this->artisan('licentra:update --force')->assertExitCode(0);
});

it('handles activation form post requests via controller', function () {
    $data = [
        'status' => 'Active',
        'license_key' => 'LCN-ACDE-FGH2-3456-7KMN',
    ];

    $json = CryptoVerifier::deterministicJsonEncode($data);
    openssl_sign($json, $rawSig, $this->privateKey, OPENSSL_ALGO_SHA256);

    Http::fake([
        'https://licentra.test/api/license/activate' => Http::response([
            'message' => 'License activated successfully',
            'data' => $data,
            'signature' => base64_encode($rawSig),
        ], 200),
    ]);

    $response = $this->post('/licentra/activate', [
        'license_key' => 'LCN-ACDE-FGH2-3456-7KMN',
    ]);

    $response->assertSessionHas('licentra_success');
});

it('fetches CRL and checks if a license is revoked', function () {
    $crlData = [
        'revoked_licenses' => [
            ['license_key' => $this->licenseKey, 'status' => 'Revoked'],
        ],
        'generated_at' => now()->toIso8601String(),
    ];

    $payloadString = CryptoVerifier::deterministicJsonEncode($crlData);
    openssl_sign($payloadString, $rawSig, $this->privateKey, OPENSSL_ALGO_SHA256);

    Http::fake([
        'https://licentra.test/api/license/crl' => Http::response([
            'data' => $crlData,
            'signature' => base64_encode($rawSig),
        ], 200),
    ]);

    $fetched = LicentraLaravel::fetchCrl();
    expect($fetched['revoked_licenses'])->toHaveCount(1);
    expect(LicentraLaravel::isRevoked($this->licenseKey, $fetched))->toBeTrue();
    expect(LicentraLaravel::isRevoked('VALID-KEY-999', $fetched))->toBeFalse();
});

it('allows minor backward clock drift within tolerance but detects major clock tampering', function () {
    config()->set('licentra-laravel.clock_drift_tolerance', 300);
    $cacheKey = "licentra_last_timestamp_{$this->licenseKey}";

    // Set last timestamp to 100 seconds ahead of current time
    Cache::put($cacheKey, encrypt(time() + 100), 3600);

    // Ping should succeed because 100 seconds is within 300 seconds tolerance
    Http::fake([
        'https://licentra.test/api/license/ping' => Http::response(['data' => ['status' => 'valid']], 200),
    ]);
    expect(LicentraLaravel::ping())->toBeTrue();

    // Now set last timestamp to 1000 seconds ahead (exceeds 300s tolerance)
    Cache::forget("licentra_ping_{$this->licenseKey}");
    Cache::put($cacheKey, encrypt(time() + 1000), 3600);
    expect(LicentraLaravel::ping())->toBeFalse();
});

it('retrieves limits and checks quota thresholds accurately', function () {
    $limits = [
        'max_users' => 10,
        'max_storage_gb' => 50,
        'unlimited_feature' => -1,
    ];

    expect(LicentraLaravel::getLimit('max_users', null, $limits))->toBe(10);
    expect(LicentraLaravel::getLimit('non_existent', 99, $limits))->toBe(99);

    expect(LicentraLaravel::hasReachedLimit('max_users', 5, $limits))->toBeFalse();
    expect(LicentraLaravel::hasReachedLimit('max_users', 10, $limits))->toBeTrue();
    expect(LicentraLaravel::hasReachedLimit('max_users', 12, $limits))->toBeTrue();
    expect(LicentraLaravel::hasReachedLimit('unlimited_feature', 9999, $limits))->toBeFalse();
});

it('runs licentra:sync artisan command successfully', function () {
    Http::fake([
        'https://licentra.test/api/license/ping' => Http::response(['data' => ['status' => 'valid']], 200),
        'https://licentra.test/api/license/crl' => Http::response(['data' => ['revoked_licenses' => []]], 200),
    ]);

    $this->artisan('licentra:sync', ['--force' => true])
        ->expectsOutputToContain('Syncing license status')
        ->assertExitCode(0);
});

it('runs licentra:install-license artisan command successfully', function () {
    $payload = [
        'license_key' => $this->licenseKey,
        'product_slug' => 'aquanusa',
        'license_type' => 'Standard',
        'valid_until' => now()->addYear()->toDateString(),
    ];

    $payloadString = CryptoVerifier::deterministicJsonEncode($payload);
    openssl_sign($payloadString, $rawSig, $this->privateKey, OPENSSL_ALGO_SHA256);

    $jsonContent = json_encode([
        'payload' => $payload,
        'signature' => base64_encode($rawSig),
    ]);

    $tempFile = storage_path('app/test_license.lic');
    File::ensureDirectoryExists(dirname($tempFile));
    File::put($tempFile, $jsonContent);

    $this->artisan('licentra:install-license', ['file' => $tempFile])
        ->expectsOutputToContain('Offline license verified and installed successfully')
        ->assertExitCode(0);

    File::delete($tempFile);
});

it('supports multi-tenant scoped instances and dynamic key resolution', function () {
    $tenantA = ['id' => 1, 'name' => 'Company A', 'license_key' => 'LCN-TENANT-A-1111'];
    $tenantB = (object) ['id' => 2, 'name' => 'Company B', 'license_key' => 'LCN-TENANT-B-2222'];

    $sdkA = LicentraLaravel::forTenant($tenantA);
    $sdkB = LicentraLaravel::forTenant($tenantB);

    expect($sdkA->getLicenseKey())->toBe('LCN-TENANT-A-1111');
    expect($sdkB->getLicenseKey())->toBe('LCN-TENANT-B-2222');

    // Test dynamic resolver
    LicentraLaravel::resolveLicenseKeyUsing(fn () => 'LCN-DYNAMIC-RESOLVED');
    $dynamicSdk = LicentraLaravel::forLicenseKey('');
    expect($dynamicSdk->getLicenseKey())->toBe('LCN-DYNAMIC-RESOLVED');
});
