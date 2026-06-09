<?php

declare(strict_types=1);

namespace App\Modules\Finance\DTOs;

use App\Modules\Finance\Enums\FinancialEntryTypeEnum;
use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

final readonly class CreateFinancialEntryDTO extends BaseDTO
{
    /**
     * @param  array<array{due_date: string, amount_cents: int}>|null  $installments
     */
    public function __construct(
        public FinancialEntryTypeEnum $type,
        public ?string                $categoryId,
        public ?string                $financialAccountId,
        public int                    $amountCents,
        public Carbon                 $dueDate,
        public ?string                $description,
        public ?string                $referenceType,
        public ?string                $referenceId,
        public ?string                $externalReference,
        public ?string                $storeId,
        public ?array                 $installments,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            type:               FinancialEntryTypeEnum::from($request->string('type')->toString()),
            categoryId:         $request->string('category_id')->value() ?: null,
            financialAccountId: $request->string('financial_account_id')->value() ?: null,
            amountCents:        $request->integer('amount_cents'),
            dueDate:            Carbon::parse($request->string('due_date')->toString()),
            description:        $request->string('description')->value() ?: null,
            referenceType:      $request->string('reference_type')->value() ?: null,
            referenceId:        $request->string('reference_id')->value() ?: null,
            externalReference:  $request->string('external_reference')->value() ?: null,
            storeId:            $request->string('store_id')->value() ?: null,
            installments:       $request->has('installments') ? $request->array('installments') : null,
        );
    }
}
