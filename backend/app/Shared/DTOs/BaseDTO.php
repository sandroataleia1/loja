<?php

declare(strict_types=1);

namespace App\Shared\DTOs;

use Illuminate\Http\Request;

abstract readonly class BaseDTO
{
    abstract public static function fromRequest(Request $request): static;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
