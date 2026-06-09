<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

final class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        Horizon::routeMailNotificationsTo(env('HORIZON_ALERT_EMAIL', ''));
    }

    protected function gate(): void
    {
        Gate::define('viewHorizon', function (mixed $user): bool {
            if (app()->isLocal()) {
                return true;
            }

            $allowedEmails = array_filter(
                explode(',', (string) env('HORIZON_ALLOWED_EMAILS', ''))
            );

            return in_array($user?->email, $allowedEmails, true);
        });
    }
}
