<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', fn () => response()->json([
        'name' => 'UDev API',
        'status' => 'ok',
    ]));

    require __DIR__.'/api/v1/auth.php';
    require __DIR__.'/api/v1/workspaces.php';
    require __DIR__.'/api/v1/navigation.php';
    require __DIR__.'/api/v1/master/customers.php';
    require __DIR__.'/api/v1/master/project_types.php';
    require __DIR__.'/api/v1/projects/project.php';
    require __DIR__.'/api/v1/projects/invoices.php';
    require __DIR__.'/api/v1/projects/payments.php';
    require __DIR__.'/api/v1/projects/expenses.php';
    require __DIR__.'/api/v1/projects/reports.php';
    require __DIR__.'/api/v1/projects/team_loans.php';
    require __DIR__.'/api/v1/access-control/menus.php';
    require __DIR__.'/api/v1/access-control/users.php';
});
