<?php

declare(strict_types=1);

use App\Modules\Conditional\Http\Controllers\ConditionalController;
use Illuminate\Support\Facades\Route;

Route::get('/',                         [ConditionalController::class, 'index'])->name('index');
Route::post('/',                        [ConditionalController::class, 'store'])->name('store');
Route::get('/{conditional}',            [ConditionalController::class, 'show'])->name('show');
Route::post('/{conditional}/return',    [ConditionalController::class, 'return'])->name('return');
Route::post('/{conditional}/convert',   [ConditionalController::class, 'convert'])->name('convert');
Route::post('/{conditional}/cancel',    [ConditionalController::class, 'cancel'])->name('cancel');
