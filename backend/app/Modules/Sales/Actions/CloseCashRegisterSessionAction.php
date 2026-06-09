<?php

declare(strict_types=1);

namespace App\Modules\Sales\Actions;

use App\Modules\Sales\DTOs\CloseSessionDTO;
use App\Modules\Sales\Enums\CashMovementTypeEnum;
use App\Modules\Sales\Enums\CashRegisterSessionStatusEnum;
use App\Modules\Sales\Events\CashDifferenceDetected;
use App\Modules\Sales\Events\CashRegisterClosed;
use App\Modules\Sales\Models\CashMovement;
use App\Modules\Sales\Models\CashRegisterSession;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CloseCashRegisterSessionAction
{
    public function execute(CashRegisterSession $session, CloseSessionDTO $dto): CashRegisterSession
    {
        if (! $session->status->canClose()) {
            throw new BusinessException(
                "Sessão de caixa já está {$session->status->label()}."
            );
        }

        return DB::transaction(function () use ($session, $dto): CashRegisterSession {
            // Recarrega com lock para consistência
            $session->refresh()->lockForUpdate()->first();

            // Calcula saldo esperado e diferença
            $expectedBalance  = $session->computeExpectedBalanceCents();
            $differenceAmount = $dto->closingAmountCents - $expectedBalance;

            $aggregates = $session->computeAggregates();

            $closingDescription = sprintf(
                'Fechamento — vendas: R$%.2f | sangrias: R$%.2f | suprimentos: R$%.2f',
                $aggregates['cash_total'] / 100,
                $aggregates['withdrawal_total'] / 100,
                $aggregates['supply_total'] / 100,
            );

            // Movimentação de fechamento (snapshot imutável)
            CashMovement::create([
                'store_id'                 => $session->store_id,
                'cash_register_session_id' => $session->uuid,
                'type'                     => CashMovementTypeEnum::Closing,
                'amount_cents'             => $dto->closingAmountCents,
                'description'              => $closingDescription,
                'created_by'               => Auth::id(),
            ]);

            $session->update([
                'status'                  => CashRegisterSessionStatusEnum::Closed,
                'closed_by'               => Auth::id(),
                'closing_amount_cents'    => $dto->closingAmountCents,
                'expected_balance_cents'  => $expectedBalance,
                'difference_amount_cents' => $differenceAmount,
                'difference_reason'       => $dto->differenceReason,
                'notes'                   => $dto->notes ?? $session->notes,
                'closed_at'               => now(),
            ]);

            $session->refresh();

            CashRegisterClosed::dispatch($session, $aggregates);

            if ($differenceAmount !== 0) {
                CashDifferenceDetected::dispatch($session, $differenceAmount, $dto->differenceReason);
            }

            return $session->load(['store', 'cashRegister', 'user', 'closedByUser']);
        });
    }
}
