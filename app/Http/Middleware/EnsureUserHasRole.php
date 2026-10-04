<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Tenancy\WorkspaceContext;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  list<string>  $roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = app(WorkspaceContext::class)->role() ?? $request->user()?->role;

        if (! in_array($role, $roles, true)) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk tindakan ini.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
