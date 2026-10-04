<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  list<string>  $roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! in_array($request->user()?->role, $roles, true)) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk tindakan ini.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
