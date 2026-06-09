<?php

declare(strict_types=1);

use App\Modules\Customers\Http\Controllers\CustomerTagController;
use Illuminate\Support\Facades\Route;

Route::get('/',                [CustomerTagController::class, 'index'])->name('index');
Route::post('/',               [CustomerTagController::class, 'store'])->name('store');
Route::put('/{customerTag}',   [CustomerTagController::class, 'update'])->name('update');
Route::delete('/{customerTag}', [CustomerTagController::class, 'destroy'])->name('destroy');
