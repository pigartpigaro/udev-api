<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AccessControl\Menu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Tenancy\WorkspaceContext;

class NavigationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $allowedPermissionIds = DB::table('role_permissions')
            ->select('permission_id')
            ->where('workspace_id', app(WorkspaceContext::class)->id())
            ->where('role', app(WorkspaceContext::class)->role());

        if (! $request->user()->is_platform_admin) {
            $platformPermissionIds = DB::table('permissions')
                ->whereIn('key', ['access-control.menus.manage', 'access-control.permissions.manage'])
                ->select('id');
            $allowedPermissionIds->whereNotIn('permission_id', $platformPermissionIds);
        }

        $menusByParent = Menu::query()
            ->where('is_active', true)
            ->where(function ($query) use ($allowedPermissionIds): void {
                $query->whereNull('permission_id')
                    ->orWhereIn('permission_id', $allowedPermissionIds);
            })
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->groupBy('parent_id');

        return response()->json([
            'menus' => $this->childrenOf(null, $menusByParent),
        ]);
    }

    /**
     * @param  Collection<int|string, Collection<int, Menu>>  $menusByParent
     * @return list<array<string, mixed>>
     */
    private function childrenOf(?int $parentId, $menusByParent): array
    {
        return $menusByParent->get($parentId, collect())
            ->map(function (Menu $menu) use ($menusByParent): array {
                $children = $this->childrenOf($menu->id, $menusByParent);

                return [
                    'key' => $menu->key,
                    'label' => $menu->label,
                    'route_name' => $menu->route_name,
                    'icon' => $menu->icon,
                    'children' => $children,
                ];
            })
            ->filter(fn (array $menu): bool => $menu['route_name'] !== null || $menu['children'] !== [])
            ->values()
            ->all();
    }
}
