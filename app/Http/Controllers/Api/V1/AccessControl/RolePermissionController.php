<?php

namespace App\Http\Controllers\Api\V1\AccessControl;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AccessControl\SyncRolePermissionsRequest;
use App\Models\AccessControl\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use App\Tenancy\WorkspaceContext;

class RolePermissionController extends Controller
{
    private const MEMBER_PROTECTED_PERMISSION_KEYS = [
        'access-control.roles.manage',
        'access-control.menus.manage',
        'access-control.permissions.manage',
        'workspaces.manage',
        'users.manage',
    ];

    public function show(string $role): JsonResponse
    {
        abort_unless(preg_match('/^[A-Za-z0-9_-]{1,30}$/', $role) === 1, Response::HTTP_NOT_FOUND);

        $permissions = Permission::query()
            ->whereIn('id', DB::table('role_permissions')->select('permission_id')
                ->where('workspace_id', app(WorkspaceContext::class)->id())
                ->where('role', $role))
            ->orderBy('key')
            ->get();

        return response()->json([
            'role' => $role,
            'permissions' => $permissions,
        ]);
    }

    public function options(string $role): JsonResponse
    {
        abort_unless($role === 'member', Response::HTTP_NOT_FOUND);

        $workspaceId = app(WorkspaceContext::class)->id();
        $selectedIds = DB::table('role_permissions')->where('workspace_id', $workspaceId)
            ->where('role', $role)->pluck('permission_id')->map(fn ($id): int => (int) $id)->all();
        $permissions = Permission::query()
            ->whereNotIn('key', self::MEMBER_PROTECTED_PERMISSION_KEYS)
            ->orderBy('key')
            ->get(['id', 'key', 'label'])
            ->map(fn (Permission $permission): array => [
                ...$permission->toArray(),
                'enabled' => in_array((int) $permission->id, $selectedIds, true),
            ]);

        return response()->json(['role' => $role, 'permissions' => $permissions]);
    }

    public function update(SyncRolePermissionsRequest $request, string $role): JsonResponse
    {
        abort_unless($role === 'member', Response::HTTP_NOT_FOUND);

        $permissionIds = $request->validated('permission_ids');
        $protectedRequested = Permission::query()->whereIn('id', $permissionIds)
            ->whereIn('key', self::MEMBER_PROTECTED_PERMISSION_KEYS)->exists();
        if ($protectedRequested) {
            return response()->json(['message' => 'Izin pengelolaan pengguna, workspace, dan hak akses hanya dapat dimiliki role admin.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $unknownIds = DB::table('permissions')->whereIn('id', $permissionIds)->count() !== count($permissionIds);
        if ($unknownIds) {
            return response()->json(['message' => 'Daftar izin tidak valid.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $workspaceId = app(WorkspaceContext::class)->id();
        DB::transaction(function () use ($permissionIds, $role, $workspaceId): void {
            DB::table('role_permissions')->where('workspace_id', $workspaceId)->where('role', $role)->delete();

            $now = now();
            $assignments = array_map(
                fn (int $permissionId): array => [
                    'role' => $role,
                    'workspace_id' => $workspaceId,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                $permissionIds,
            );

            if ($assignments !== []) {
                DB::table('role_permissions')->insert($assignments);
            }
        });

        return $this->show($role);
    }
}
