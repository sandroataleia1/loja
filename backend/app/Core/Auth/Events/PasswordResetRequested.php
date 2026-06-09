<?php

declare(strict_types=1);

namespace App\Core\Auth\Events;

use App\Core\Auth\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PasswordResetRequested
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly User $user,
    ) {}
}
