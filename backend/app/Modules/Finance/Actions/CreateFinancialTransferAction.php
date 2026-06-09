<?php

declare(strict_types=1);

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\DTOs\CreateFinancialTransferDTO;
use App\Modules\Finance\Events\FinancialTransferCreated;
use App\Modules\Finance\Models\FinancialAccount;
use App\Modules\Finance\Models\FinancialTransfer;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CreateFinancialTransferAction
{
    /**
     * Transfere valor entre duas contas financeiras do mesmo tenant.
     *
     * Débita origem e credita destino em uma única transação atômica.
     * lockForUpdate garante que o saldo não seja corrompido em concorrência.
     */
    public function execute(CreateFinancialTransferDTO $dto): FinancialTransfer
    {
        if ($dto->originAccountId === $dto->destinationAccountId) {
            throw new BusinessException('Conta de origem e destino não podem ser iguais.');
        }

        if ($dto->amountCents <= 0) {
            throw new BusinessException('Valor da transferência deve ser maior que zero.');
        }

        return DB::transaction(function () use ($dto): FinancialTransfer {
            // Locks em ordem determinística (menor UUID primeiro) para evitar deadlock
            $ids = collect([$dto->originAccountId, $dto->destinationAccountId])->sort()->values();

            $accounts = FinancialAccount::whereIn('uuid', $ids)
                ->orderBy('uuid')
                ->lockForUpdate()
                ->get()
                ->keyBy('uuid');

            $origin      = $accounts->get($dto->originAccountId);
            $destination = $accounts->get($dto->destinationAccountId);

            if ($origin === null || $destination === null) {
                throw new BusinessException('Uma ou ambas as contas não foram encontradas.');
            }

            $origin->decrement('current_balance_cents', $dto->amountCents);
            $destination->increment('current_balance_cents', $dto->amountCents);

            $transfer = FinancialTransfer::create([
                'origin_account_id'      => $dto->originAccountId,
                'destination_account_id' => $dto->destinationAccountId,
                'amount_cents'           => $dto->amountCents,
                'transferred_at'         => $dto->transferredAt,
                'description'            => $dto->description,
                'created_by'             => Auth::id(),
            ]);

            FinancialTransferCreated::dispatch($transfer->load(['originAccount', 'destinationAccount']));

            return $transfer->load(['originAccount', 'destinationAccount', 'creator']);
        });
    }
}
