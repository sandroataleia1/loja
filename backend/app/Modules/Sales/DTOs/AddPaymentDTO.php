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
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            method:            PaymentMethodEnum::from($request->string('method')->toString()),
            amountCents:       $request->integer('amount_cents'),
            externalReference: $request->string('external_reference')->value() ?: null,
            notes:             $request->string('notes')->value() ?: null,
            metadata:          $request->array('metadata') ?: null,
        );
    }
}
