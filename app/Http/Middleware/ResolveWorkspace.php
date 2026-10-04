<?php

namespace App\Http\Middleware;

use App\Tenancy\WorkspaceContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ResolveWorkspace
{
    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->user()?->id;
        abort_unless($userId, Response::HTTP_UNAUTHORIZED);

        $memberships = DB::table('workspace_user')
            ->join('workspaces', 'workspaces.id', '=', 'workspace_user.workspace_id')
            ->where('workspace_user.user_id', $userId)
            ->where('workspaces.is_active', true)
            ->select('workspaces.id', 'workspace_user.role')
            ->get();

        $requestedId = $request->header('X-Workspace-ID');
        if ($requestedId !== null && (! ctype_digit($requestedId) || (int) $requestedId < 1)) {
            return response()->json(['message' => 'Workspace tidak valid.'], Response::HTTP_BAD_REQUEST);
        }

        if ($requestedId !== null) {
            $membership = $memberships->firstWhere('id', (int) $requestedId);
            if ($membership === null) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke workspace ini.'], Response::HTTP_FORBIDDEN);
            }
        } elseif ($memberships->count() === 1) {
            $membership = $memberships->first();
        } elseif ($memberships->isEmpty()) {
            return response()->json(['message' => 'Akun belum tergabung ke workspace. Hubungi administrator UDev.'], Response::HTTP_FORBIDDEN);
        } else {
            return response()->json(['message' => 'Pilih workspace aktif melalui header X-Workspace-ID.'], Response::HTTP_CONFLICT);
        }

        app(WorkspaceContext::class)->activate((int) $membership->id, $membership->role);
        $request->attributes->set('workspace_id', (int) $membership->id);
        $request->attributes->set('workspace_role', $membership->role);

        try {
            return $next($request);
        } finally {
            app(WorkspaceContext::class)->clear();
        }
    }
}
