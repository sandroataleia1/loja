<?php

declare(strict_types=1);

use App\Core\Tenancy\Http\Controllers\OnboardingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Onboarding — configuração inicial do tenant por perfil de negócio
|--------------------------------------------------------------------------
*/

Route::get('profiles', [OnboardingController::class, 'profiles'])->name('profiles');
Route::post('profile', [OnboardingController::class, 'applyProfile'])->name('profile.apply');
