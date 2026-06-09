<?php

declare(strict_types=1);

namespace App\Modules\Finance\DTOs;

use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

final readonly class PayFinancialEntryDTO extends BaseDTO
{
    public function __construct(
        public ?string $financialAccountId,
        public Carbon  $paidAt,
        public ?int    $installmentNumber,
        public ?int    $amountCents,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            financialAccountId: $request->string('financial_account_id')->value() ?: null,
            paidAt:             $request->has('paid_at')
                                    ? Carbon::parse($request->string('paid_at')->toString())
                                    : now(),
            installmentNumber:  $request->has('installment_number')
                                    ? $request->integer('installment_number')
                                    : null,
            amountCents:        $request->has('amount_cents')
                                    ? $request->integer('amount_cents')
                                    : null,
        );
    }
}
