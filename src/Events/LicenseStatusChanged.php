<?php

declare(strict_types=1);

namespace Licentra\LicentraLaravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LicenseStatusChanged
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $licenseKey,
        public string $status,
        public array $payload = []
    ) {}
}
