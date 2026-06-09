<?php

declare(strict_types=1);

namespace App\Modules\Sales\DTOs;

use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class OpenSessionDTO extends BaseDTO
{
    public function __construct(
        public string  $storeId,
        public int     $openingAmountCents,
        public ?string $cashRegisterId,
        public ?string $notes,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            storeId:             $request->string('store_id')->toString(),
            openingAmountCents:  $request->integer('opening_amount_cents', 0),
            cashRegisterId:      $request->string('cash_register_id')->value() ?: null,
            notes:               $request->string('notes')->value() ?: null,
        );
    }
}
