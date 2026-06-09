<?php

declare(strict_types=1);

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\DTOs\CreateFinancialEntryDTO;
use App\Modules\Finance\Enums\FinancialEntryStatusEnum;
use App\Modules\Finance\Enums\FinancialInstallmentStatusEnum;
use App\Modules\Finance\Events\FinancialEntryCreated;
use App\Modules\Finance\Models\FinancialEntry;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\DB;

final readonly class CreateFinancialEntryAction
{
    /**
     * Cria lançamento financeiro, com ou sem parcelas.
     *
     * Com parcelas: amount_cents deve ser igual à soma das parcelas.
     * Sem parcelas: lançamento único.
     */
    public function execute(CreateFinancialEntryDTO $dto): FinancialEntry
    {
        if ($dto->installments !== null && $dto->installments !== []) {
            $this->validateInstallments($dto);
        }

        return DB::transaction(function () use ($dto): FinancialEntry {
            $entry = FinancialEntry::create([
                'store_id'              => $dto->storeId,
                'type'                  => $dto->type,
                'category_id'           => $dto->categoryId,
                'financial_account_id'  => $dto->financialAccountId,
                'amount_cents'          => $dto->amountCents,
                'due_date'              => $dto->dueDate,
                'status'                => FinancialEntryStatusEnum::Pending,
                'description'           => $dto->description,
                'reference_type'        => $dto->referenceType,
                'reference_id'          => $dto->referenceId,
                'external_reference'    => $dto->externalReference,
            ]);

            if ($dto->installments !== null && $dto->installments !== []) {
                foreach ($dto->installments as $i => $installment) {
                    $entry->installments()->create([
                        'installment_number' => $i + 1,
                        'due_date'           => $installment['due_date'],
                        'amount_cents'       => $installment['amount_cents'],
                        'status'             => FinancialInstallmentStatusEnum::Pending,
                    ]);
                }
            }

            FinancialEntryCreated::dispatch($entry->load('installments'));

            return $entry->load(['installments', 'category', 'account']);
        });
    }

    private function validateInstallments(CreateFinancialEntryDTO $dto): void
    {
        $total = array_sum(array_column($dto->installments, 'amount_cents'));

        if ($total !== $dto->amountCents) {
            throw new BusinessException(
                "Soma das parcelas ({$total}) não corresponde ao total do lançamento ({$dto->amountCents})."
            );
        }
    }
}
