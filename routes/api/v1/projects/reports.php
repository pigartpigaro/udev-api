<?php

use App\Http\Controllers\Api\V1\Projects\ProjectFinancialReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'workspace', 'permission:projects.reports.view'])->group(function (): void {
    Route::get('projects/reports/financial', ProjectFinancialReportController::class);
});
