<?php

use App\Http\Controllers\Api\V1\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::get('/workspaces', [WorkspaceController::class, 'index'])
    ->middleware('auth:sanctum');

Route::middleware(['auth:sanctum', 'workspace', 'permission:workspaces.manage'])->group(function (): void {
    Route::get('/workspaces/current', [WorkspaceController::class, 'current']);
    Route::patch('/workspaces/current', [WorkspaceController::class, 'updateCurrent']);
});
