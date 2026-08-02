<?php

declare(strict_types=1);

namespace Licentra\LicentraLaravel\Support;

/**
 * Client-side license key format validator.
 *
 * Expected format: LCN-XXXX-XXXX-XXXX-XXXX
 * Where X is from the unambiguous alphabet: ACDEFGHJKMNPQRSTUVWXYZ2345679
 */
final class LicenseKeyValidator
{
    /**
     * Regex pattern matching the Licentra license key format.
     */
    public const FORMAT_REGEX = '/^LCN-[ACDEFGHJKMNPQRSTUVWXYZ2345679]{4}-[ACDEFGHJKMNPQRSTUVWXYZ2345679]{4}-[ACDEFGHJKMNPQRSTUVWXYZ2345679]{4}-[ACDEFGHJKMNPQRSTUVWXYZ2345679]{4}$/';

    /**
     * Validate that a license key matches the expected Licentra format.
     */
    public static function isValid(string $key): bool
    {
        return (bool) preg_match(self::FORMAT_REGEX, strtoupper(trim($key)));
    }

    /**
     * Normalize a license key to uppercase with trimmed whitespace.
     */
    public static function normalize(string $key): string
    {
        return strtoupper(trim($key));
    }
}
