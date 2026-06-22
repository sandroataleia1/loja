<?php

declare(strict_types=1);

namespace App\Modules\Sales\DTOs;

use App\Modules\Fiscal\Enums\FiscalModeEnum;
use App\Modules\Sales\Enums\SalesChannelEnum;
use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class CreateSaleDTO extends BaseDTO
{
    /**
     * @param CreateSaleItemDTO[]                                                              $items
     * @param array<array{method:string,amount_cents:int,external_reference?:string,notes?:string,metadata?:array}> $payments
     */
    public function __construct(
        public string         $storeId,
        public ?string        $sessionId,
        public ?string        $customerId,
        public ?string        $sellerId,
        public array          $items,
        public ?string        $syncUuid,
        public ?string        $notes,
        public ?array         $metadata,
        public ?FiscalModeEnum   $fiscalMode         = null,
        public SalesChannelEnum  $salesChannel       = SalesChannelEnum::Pdv,
        public array             $payments           = [],
        public ?string           $paymentMethodId    = null,
        public ?string           $paymentConditionId = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $fiscalModeRaw = $request->string('fiscal_mode')->value() ?: null;

        return new static(
            storeId:    $request->string('store_id')->toString(),
            sessionId:  $request->string('session_id')->value() ?: null,
            customerId: $request->string('customer_id')->value() ?: null,
            sellerId:   $request->string('seller_id')->value() ?: null,
            items:      array_map(
                fn (array $i) => CreateSaleItemDTO::fromArray($i),
                $request->array('items'),
            ),
            syncUuid:   $request->string('sync_uuid')->value() ?: null,
            notes:      $request->string('notes')->value() ?: null,
            metadata:     $request->array('metadata') ?: null,
            fiscalMode:         $fiscalModeRaw !== null ? FiscalModeEnum::from($fiscalModeRaw) : null,
            salesChannel:       SalesChannelEnum::tryFrom($request->string('sales_channel')->value() ?: '') ?? SalesChannelEnum::Pdv,
            payments:           $request->array('payments') ?: [],
            paymentMethodId:    $request->string('payment_method_id')->value() ?: null,
            paymentConditionId: $request->string('payment_condition_id')->value() ?: null,
        );
    }
}
