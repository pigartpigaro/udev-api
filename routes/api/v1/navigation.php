<?php

use App\Http\Controllers\Api\V1\NavigationController;
use Illuminate\Support\Facades\Route;

Route::get('/navigation', [NavigationController::class, 'index'])
    ->middleware(['auth:sanctum', 'workspace']);
