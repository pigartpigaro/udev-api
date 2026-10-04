<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformAdministrator
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->is_platform_admin) {
            return response()->json(['message' => 'Aksi ini hanya tersedia untuk administrator platform UDev.'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
