<?php

declare(strict_types=1);

namespace App\Modules\Finance\DTOs;

use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

final readonly class CreateFinancialTransferDTO extends BaseDTO
{
    public function __construct(
        public string  $originAccountId,
        public string  $destinationAccountId,
        public int     $amountCents,
        public Carbon  $transferredAt,
        public ?string $description,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            originAccountId:      $request->string('origin_account_id')->toString(),
            destinationAccountId: $request->string('destination_account_id')->toString(),
            amountCents:          $request->integer('amount_cents'),
            transferredAt:        $request->has('transferred_at')
                                      ? Carbon::parse($request->string('transferred_at')->toString())
                                      : now(),
            description:          $request->string('description')->value() ?: null,
        );
    }
}
