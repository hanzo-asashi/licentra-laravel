<?php

declare(strict_types=1);

namespace Licentra\LicentraLaravel\Support;

use Exception;
use RuntimeException;

class CryptoVerifier
{
    /**
     * Verify RSA-256 signature (SHA256withRSA) against data string.
     */
    public static function verifySignature(string $data, string $signature, string $publicKey): bool
    {
        $decodedSignature = base64_decode($signature, true);
        if ($decodedSignature === false) {
            return false;
        }

        $result = openssl_verify($data, $decodedSignature, $publicKey, OPENSSL_ALGO_SHA256);

        return $result === 1;
    }

    /**
     * Verify offline license file content (.lic JSON format).
     *
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    public static function verifyOfflineLicense(string $jsonContent, string $publicKey): array
    {
        $data = json_decode($jsonContent, true);
        if (! is_array($data) || ! isset($data['payload'], $data['signature'])) {
            throw new RuntimeException('Invalid offline license file format.');
        }

        /** @var array<string, mixed> $payload */
        $payload = $data['payload'];
        /** @var string $signature */
        $signature = $data['signature'];

        $payloadString = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($payloadString === false) {
            throw new RuntimeException('Failed to encode offline license payload.');
        }

        if (! self::verifySignature($payloadString, $signature, $publicKey)) {
            throw new RuntimeException('Offline license signature verification failed.');
        }

        // Validate expiration if valid_until exists
        if (! empty($payload['valid_until'])) {
            $validUntil = strtotime((string) $payload['valid_until']);
            if ($validUntil !== false && time() > $validUntil) {
                throw new RuntimeException('Offline license has expired.');
            }
        }

        // Validate valid_from if exists
        if (! empty($payload['valid_from'])) {
            $validFrom = strtotime((string) $payload['valid_from']);
            if ($validFrom !== false && time() < $validFrom) {
                throw new RuntimeException('Offline license is not yet active.');
            }
        }

        return $payload;
    }

    /**
     * Verify RS256 JWT license token.
     *
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    public static function verifyJwt(string $jwt, string $publicKey): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid JWT format.');
        }

        [$headerB64, $payloadB64, $sigB64] = $parts;
        $signatureInput = $headerB64.'.'.$payloadB64;
        $rawSignature = self::base64UrlDecode($sigB64);

        $result = openssl_verify($signatureInput, $rawSignature, $publicKey, OPENSSL_ALGO_SHA256);
        if ($result !== 1) {
            throw new RuntimeException('Invalid JWT signature.');
        }

        $decodedPayload = self::base64UrlDecode($payloadB64);
        /** @var array<string, mixed>|null $payload */
        $payload = json_decode($decodedPayload, true);
        if (! is_array($payload)) {
            throw new RuntimeException('Invalid JWT payload.');
        }

        if (isset($payload['exp']) && time() > (int) $payload['exp']) {
            throw new RuntimeException('JWT license token has expired.');
        }

        return $payload;
    }

    private static function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder !== 0) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/')) ?: '';
    }
}
