<?php

declare(strict_types=1);

namespace Licentra\LicentraLaravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Licentra\LicentraLaravel\Facades\LicentraLaravel;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureIsActive
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (! LicentraLaravel::hasFeature($feature)) {
            abort(403, "Fitur '{$feature}' tidak diaktifkan pada lisensi Anda.");
        }

        return $next($request);
    }
}
