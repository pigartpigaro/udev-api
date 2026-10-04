<?php

use App\Http\Controllers\Api\V1\Master\ProjectTypeController;
use Illuminate\Support\Facades\Route;

Route::prefix('master')
    ->middleware(['auth:sanctum', 'workspace', 'permission:master.project-types.manage'])
    ->group(function (): void {
        Route::apiResource('project-types', ProjectTypeController::class)
            ->parameters(['project-types' => 'project_type'])
            ->except(['destroy']);
    });
