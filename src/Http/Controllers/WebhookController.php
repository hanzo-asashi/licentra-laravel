<?php

declare(strict_types=1);

namespace Licentra\LicentraLaravel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Licentra\LicentraLaravel\Events\HwidResetApproved;
use Licentra\LicentraLaravel\Events\LicenseRevoked;
use Licentra\LicentraLaravel\Events\LicenseStatusChanged;
use Licentra\LicentraLaravel\Facades\LicentraLaravel;
use Licentra\LicentraLaravel\Support\CryptoVerifier;

class WebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var string|null $signature */
        $signature = $request->header('X-Licentra-Signature') ?? $request->input('signature');
        /** @var string|array<string, mixed>|null $event */
        $event = $request->input('event');
        /** @var array<string, mixed> $payload */
        $payload = $request->input('data', []);
        /** @var string|null $licenseKey */
        $licenseKey = $payload['license_key'] ?? $request->input('license_key');

        $pubKey = LicentraLaravel::getPublicKey();
        if (! empty($signature) && ! empty($pubKey)) {
            $jsonPayload = CryptoVerifier::deterministicJsonEncode($payload);
            if (! CryptoVerifier::verifySignature($jsonPayload, $signature, $pubKey)) {
                return response()->json(['message' => 'Invalid webhook signature'], 401);
            }
        }

        if (! empty($licenseKey)) {
            LicentraLaravel::clearCache($licenseKey);
        }

        /** @var string $status */
        $status = $payload['status'] ?? 'Updated';

        if ($event === 'license.revoked' || strtolower($status) === 'revoked') {
            event(new LicenseRevoked($licenseKey ?? '', $payload));
        } elseif ($event === 'hwid.reset_approved') {
            event(new HwidResetApproved($licenseKey ?? '', $payload));
        } else {
            event(new LicenseStatusChanged($licenseKey ?? '', $status, $payload));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Licentra webhook processed successfully',
        ]);
    }
}
