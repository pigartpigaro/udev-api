<?php

use App\Http\Controllers\Api\V1\Projects\ProjectExpenseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'workspace', 'permission:projects.expenses.manage'])->group(function (): void {
    Route::get('expenses', [ProjectExpenseController::class, 'index']);
    Route::post('expenses', [ProjectExpenseController::class, 'store']);
    Route::post('expenses/{expense}/void', [ProjectExpenseController::class, 'void']);
});
