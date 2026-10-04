<?php

use App\Http\Controllers\Api\V1\Projects\ProjectController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'workspace', 'permission:projects.manage'])
    ->apiResource('projects', ProjectController::class)
    ->except(['destroy']);
