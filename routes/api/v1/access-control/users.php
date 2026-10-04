<?php

use App\Http\Controllers\Api\V1\AccessControl\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'workspace', 'role:admin', 'permission:users.manage'])->group(function (): void {
    Route::get('/access-control/users', [UserController::class, 'index']);
    Route::post('/access-control/users', [UserController::class, 'store'])->middleware('throttle:users');
    Route::patch('/access-control/users/{userId}/role', [UserController::class, 'updateRole'])->whereNumber('userId');
});
