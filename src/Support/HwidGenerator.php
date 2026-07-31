<?php

declare(strict_types=1);

namespace Licentra\LicentraLaravel\Support;

class HwidGenerator
{
    /**
     * Generate a unique, deterministic hardware fingerprint (HWID) for the current machine.
     */
    public static function generate(): string
    {
        $components = [
            'hostname' => gethostname() ?: 'unknown-host',
            'os' => PHP_OS_FAMILY,
            'uname' => php_uname('s').'-'.php_uname('r').'-'.php_uname('m'),
            'user' => getenv('USERNAME') ?: getenv('USER') ?: 'unknown-user',
        ];

        // Additional OS specific hardware details
        if (PHP_OS_FAMILY === 'Windows') {
            $systemDrive = getenv('SystemDrive') ?: 'C:';
            $components['disk'] = @disk_total_space($systemDrive);
        } else {
            $components['disk'] = @disk_total_space('/');
        }

        return hash('sha256', json_encode($components));
    }
}
