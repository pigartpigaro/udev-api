<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Tenancy\WorkspaceContext;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    public function handle(Request $request, Closure $next, string $permissionKey): Response
    {
        $hasPermission = DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('role_permissions.workspace_id', app(WorkspaceContext::class)->id())
            ->where('role_permissions.role', app(WorkspaceContext::class)->role())
            ->where('permissions.key', $permissionKey)
            ->exists();

        if (! $hasPermission) {
            return response()->json([
                'message' => 'Anda tidak memiliki izin untuk tindakan ini.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
