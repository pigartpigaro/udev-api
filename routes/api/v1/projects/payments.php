<?php

use App\Http\Controllers\Api\V1\Projects\ProjectPaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'workspace', 'permission:projects.payments.manage'])->group(function (): void {
    Route::get('payments', [ProjectPaymentController::class, 'index']);
    Route::get('payments/eligible-invoices', [ProjectPaymentController::class, 'eligibleInvoices']);
    Route::get('payments/{payment}', [ProjectPaymentController::class, 'show']);
    Route::post('payments', [ProjectPaymentController::class, 'store']);
    Route::post('payments/{payment}/void', [ProjectPaymentController::class, 'void']);
});
