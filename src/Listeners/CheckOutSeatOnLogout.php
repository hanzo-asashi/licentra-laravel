<?php

declare(strict_types=1);

namespace Licentra\LicentraLaravel\Listeners;

use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Session;
use Licentra\LicentraLaravel\Facades\LicentraLaravel;

class CheckOutSeatOnLogout
{
    public function handle(Logout $event): void
    {
        try {
            $sessionId = Session::getId();
            if (! empty($sessionId)) {
                LicentraLaravel::checkOutSeat($sessionId);
            }
        } catch (\Throwable $e) {
            // Ignore error during logout clean up
        }
    }
}
