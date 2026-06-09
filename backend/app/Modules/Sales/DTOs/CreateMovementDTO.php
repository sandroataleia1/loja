<?php

declare(strict_types=1);

namespace App\Modules\Sales\DTOs;

use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class CreateMovementDTO extends BaseDTO
{
    public function __construct(
        public int     $amountCents,
        public ?string $description,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            amountCents: $request->integer('amount_cents'),
            description: $request->string('description')->value() ?: null,
        );
    }
}
