<?php

declare(strict_types=1);

namespace Licentra\LicentraLaravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Licentra\LicentraLaravel\Facades\LicentraLaravel;
use Symfony\Component\HttpFoundation\Response;

class KeepSeatAliveMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasSession()) {
            $sessionId = $request->session()->getId();
            $user = $request->user();
            /** @var string|int|null $userId */
            $userId = $user !== null ? $user->getAuthIdentifier() : null;
            $userIdentifier = $userId !== null ? (string) $userId : null;

            if (! empty($sessionId)) {
                $cacheKey = "licentra_seat_heartbeat_{$sessionId}";
                if (! Cache::has($cacheKey)) {
                    try {
                        LicentraLaravel::keepSeatAlive($sessionId, $userIdentifier);
                        Cache::put($cacheKey, true, 300); // 5 min local heartbeat throttle
                    } catch (\Throwable $e) {
                        // Ignore heartbeat error on request level
                    }
                }
            }
        }

        return $next($request);
    }
}
