<?php

use App\Http\Controllers\Api\V1\Projects\ProjectInvoiceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'workspace', 'permission:projects.invoices.manage'])->group(function (): void {
    Route::get('invoices', [ProjectInvoiceController::class, 'index']);
    Route::post('invoices', [ProjectInvoiceController::class, 'store']);
    Route::get('invoices/{invoice}', [ProjectInvoiceController::class, 'show']);
    Route::patch('invoices/{invoice}', [ProjectInvoiceController::class, 'update']);
    Route::post('invoices/{invoice}/issue', [ProjectInvoiceController::class, 'issue']);
    Route::post('invoices/{invoice}/cancel', [ProjectInvoiceController::class, 'cancel']);
});
