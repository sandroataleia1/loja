<?php

declare(strict_types=1);

namespace App\Modules\Sales\Actions;

use App\Modules\Sales\DTOs\CreateMovementDTO;
use App\Modules\Sales\Enums\CashMovementTypeEnum;
use App\Modules\Sales\Events\CashWithdrawalCreated;
use App\Modules\Sales\Models\CashMovement;
use App\Modules\Sales\Models\CashRegisterSession;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CreateWithdrawalAction
{
    /**
     * Sangria: retira valor físico do caixa.
     *
     * Registra movimentação imutável com amount negativo.
     * Não fecha nem altera o status da sessão.
     */
    public function execute(CashRegisterSession $session, CreateMovementDTO $dto): CashMovement
    {
        if (! $session->isOpen()) {
            throw new BusinessException('Sangria só pode ser realizada em sessão aberta.');
        }

        if ($dto->amountCents <= 0) {
            throw new BusinessException('Valor da sangria deve ser maior que zero.');
        }

        return DB::transaction(function () use ($session, $dto): CashMovement {
            $movement = CashMovement::create([
                'store_id'                 => $session->store_id,
                'cash_register_session_id' => $session->uuid,
                'type'                     => CashMovementTypeEnum::Withdrawal,
                'amount_cents'             => $dto->amountCents,
                'description'              => $dto->description,
                'created_by'               => Auth::id(),
            ]);

            CashWithdrawalCreated::dispatch($session, $movement);

            return $movement;
        });
    }
}
