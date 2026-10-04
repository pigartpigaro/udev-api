<?php

use App\Http\Controllers\Api\V1\Projects\TeamLoanController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'workspace', 'permission:finance.team-loans.manage'])->group(function (): void {
    Route::get('team-loans/summary', [TeamLoanController::class, 'summary']);
    Route::get('team-loans/members', [TeamLoanController::class, 'members']);
    Route::get('team-loans', [TeamLoanController::class, 'index']);
    Route::post('team-loans', [TeamLoanController::class, 'store']);
    Route::get('team-loans/{loan}', [TeamLoanController::class, 'show']);
    Route::post('team-loans/{loan}/repayments', [TeamLoanController::class, 'repay']);
    Route::post('team-loans/{loan}/void', [TeamLoanController::class, 'voidLoan']);
    Route::post('team-loans/{loan}/repayments/{repayment}/void', [TeamLoanController::class, 'voidRepayment']);
});
