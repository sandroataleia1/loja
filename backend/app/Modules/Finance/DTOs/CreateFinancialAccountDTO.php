<?php

declare(strict_types=1);

namespace App\Modules\Finance\DTOs;

use App\Modules\Finance\Enums\FinancialAccountTypeEnum;
use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class CreateFinancialAccountDTO extends BaseDTO
{
    public function __construct(
        public ?string                  $storeId,
        public string                   $code,
        public string                   $name,
        public FinancialAccountTypeEnum $type,
        public bool                     $isActive,
        public int                      $openingBalanceCents,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            storeId:             $request->string('store_id')->value() ?: null,
            code:                $request->string('code')->toString(),
            name:                $request->string('name')->toString(),
            type:                FinancialAccountTypeEnum::from($request->string('type')->toString()),
            isActive:            $request->boolean('is_active', true),
            openingBalanceCents: $request->integer('opening_balance_cents', 0),
        );
    }
}
