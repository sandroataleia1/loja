<?php

declare(strict_types=1);

namespace App\Modules\Sales\DTOs;

use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class CloseSessionDTO extends BaseDTO
{
    public function __construct(
        public int     $closingAmountCents,
        public ?string $differenceReason,
        public ?string $notes,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            closingAmountCents: $request->integer('closing_amount_cents'),
            differenceReason:   $request->string('difference_reason')->value() ?: null,
            notes:              $request->string('notes')->value() ?: null,
        );
    }
}
