<?php

namespace Licentra\LicentraLaravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Licentra\LicentraLaravel\Facades\LicentraLaravel;
use Symfony\Component\HttpFoundation\Response;

class EnsureLicenseIsValid
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! LicentraLaravel::ping()) {
            abort(403, 'Lisensi aplikasi tidak valid atau telah kedaluwarsa.');
        }

        return $next($request);
    }
}
