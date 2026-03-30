<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Route;

abstract class Controller
{
    public function __construct()
    {
        Route::get('/health/dashboard', [HealthController::class, 'dashboard']);
    }
}
