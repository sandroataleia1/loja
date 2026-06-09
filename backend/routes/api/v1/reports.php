<?php

declare(strict_types=1);

use App\Modules\Reports\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('dashboard', DashboardController::class)->name('dashboard');
