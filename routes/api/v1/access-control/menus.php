<?php

use App\Http\Controllers\Api\V1\AccessControl\MenuController;
use App\Http\Controllers\Api\V1\AccessControl\PermissionController;
use App\Http\Controllers\Api\V1\AccessControl\RolePermissionController;
use Illuminate\Support\Facades\Route;

Route::prefix('access-control')->middleware(['auth:sanctum', 'workspace'])->group(function (): void {
    Route::apiResource('menus', MenuController::class)
        ->middleware(['platform-admin', 'permission:access-control.menus.manage']);

    Route::get('/permissions', [PermissionController::class, 'index'])
        ->middleware(['platform-admin', 'permission:access-control.permissions.manage']);
    Route::post('/permissions', [PermissionController::class, 'store'])
        ->middleware(['platform-admin', 'permission:access-control.permissions.manage']);
    Route::patch('/permissions/{permission}', [PermissionController::class, 'update'])
        ->middleware(['platform-admin', 'permission:access-control.permissions.manage']);

    Route::get('/roles/{role}/permissions', [RolePermissionController::class, 'show'])
        ->middleware('permission:access-control.roles.manage');
    Route::get('/roles/{role}/permission-options', [RolePermissionController::class, 'options'])
        ->middleware('permission:access-control.roles.manage');
    Route::put('/roles/{role}/permissions', [RolePermissionController::class, 'update'])
        ->middleware('permission:access-control.roles.manage');
});
