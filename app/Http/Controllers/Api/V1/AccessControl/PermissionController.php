<?php

namespace App\Http\Controllers\Api\V1\AccessControl;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AccessControl\StorePermissionRequest;
use App\Http\Requests\Api\V1\AccessControl\UpdatePermissionRequest;
use App\Models\AccessControl\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Tenancy\WorkspaceContext;
use Symfony\Component\HttpFoundation\Response;

class PermissionController extends Controller
{
    public function index(): JsonResponse
    {
        $permissions = Permission::query()
            ->orderBy('key')
            ->get()
            ->map(function (Permission $permission): array {
                $permission->setAttribute('roles', DB::table('role_permissions')
                    ->where('workspace_id', app(WorkspaceContext::class)->id())
                    ->where('permission_id', $permission->id)
                    ->orderBy('role')
                    ->pluck('role'));

                return $permission->toArray();
            });

        return response()->json(['permissions' => $permissions]);
    }

    public function store(StorePermissionRequest $request): JsonResponse
    {
        $permission = Permission::create($request->validated());

        return response()->json(['permission' => $permission], Response::HTTP_CREATED);
    }

    public function update(UpdatePermissionRequest $request, Permission $permission): JsonResponse
    {
        $permission->update($request->validated());

        return response()->json(['permission' => $permission->fresh()]);
    }
}
