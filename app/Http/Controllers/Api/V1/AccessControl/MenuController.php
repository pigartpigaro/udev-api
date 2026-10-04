<?php

namespace App\Http\Controllers\Api\V1\AccessControl;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AccessControl\StoreMenuRequest;
use App\Http\Requests\Api\V1\AccessControl\UpdateMenuRequest;
use App\Models\AccessControl\Menu;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class MenuController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'menus' => Menu::query()
                ->with('permission:id,key,label')
                ->orderBy('parent_id')
                ->orderBy('sort_order')
                ->orderBy('label')
                ->get(),
        ]);
    }

    public function store(StoreMenuRequest $request): JsonResponse
    {
        $menu = Menu::create($request->validated());

        return response()->json([
            'menu' => $menu->load('permission:id,key,label'),
        ], Response::HTTP_CREATED);
    }

    public function show(Menu $menu): JsonResponse
    {
        return response()->json([
            'menu' => $menu->load('permission:id,key,label'),
        ]);
    }

    public function update(UpdateMenuRequest $request, Menu $menu): JsonResponse
    {
        $parentId = $request->validated('parent_id');
        $parent = $parentId ? Menu::find($parentId) : null;

        while ($parent !== null) {
            if ($parent->is($menu)) {
                return response()->json([
                    'message' => 'Menu tidak dapat dipindahkan ke dalam turunannya sendiri.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $parent = $parent->parent;
        }

        $menu->update($request->validated());

        return response()->json([
            'menu' => $menu->fresh()->load('permission:id,key,label'),
        ]);
    }

    public function destroy(Menu $menu): Response|JsonResponse
    {
        if ($menu->children()->exists()) {
            return response()->json([
                'message' => 'Menu induk tidak dapat dihapus selama masih memiliki submenu.',
            ], Response::HTTP_CONFLICT);
        }

        $menu->delete();

        return response()->noContent();
    }
}
