<?php

declare(strict_types=1);

namespace App\Modules\Sales\DTOs;

use App\Modules\Sales\Enums\PaymentMethodEnum;
use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class AddPaymentDTO extends BaseDTO
{
    public function __construct(
        public PaymentMethodEnum $method,
        public int               $amountCents,
        public ?string           $externalReference,
        public ?string           $notes,
        public ?array            $metadata,
        public ?string           $paymentMethodId    = null,
        public ?string           $paymentConditionId = null,
        public int               $discountCents      = 0,
        public int               $interestCents      = 0,
        public int               $installmentNumber  = 1,
        public int               $totalInstallments  = 1,
        public ?string           $dueDate            = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            method:             PaymentMethodEnum::from($request->string('method')->toString()),
            amountCents:        $request->integer('amount_cents'),
            externalReference:  $request->string('external_reference')->value() ?: null,
            notes:              $request->string('notes')->value() ?: null,
            metadata:           $request->array('metadata') ?: null,
            paymentMethodId:    $request->string('payment_method_id')->value() ?: null,
            paymentConditionId: $request->string('payment_condition_id')->value() ?: null,
            discountCents:      $request->integer('discount_cents', 0),
            interestCents:      $request->integer('interest_cents', 0),
            installmentNumber:  $request->integer('installment_number', 1),
            totalInstallments:  $request->integer('total_installments', 1),
            dueDate:            $request->string('due_date')->value() ?: null,
        );
    }
}
