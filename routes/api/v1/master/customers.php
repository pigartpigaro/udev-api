<?php

use App\Http\Controllers\Api\V1\Master\CustomerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'workspace', 'permission:master.customers.manage'])->group(function (): void {
    Route::apiResource('customers', CustomerController::class);
});
