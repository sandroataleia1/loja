<?php

declare(strict_types=1);

namespace App\Modules\Sales\Actions;

use App\Modules\Sales\DTOs\CreateMovementDTO;
use App\Modules\Sales\Enums\CashMovementTypeEnum;
use App\Modules\Sales\Events\CashSupplyCreated;
use App\Modules\Sales\Models\CashMovement;
use App\Modules\Sales\Models\CashRegisterSession;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CreateSupplyAction
{
    /**
     * Suprimento: adiciona valor físico ao caixa (reforço).
     *
     * Registra movimentação imutável com amount positivo.
     */
    public function execute(CashRegisterSession $session, CreateMovementDTO $dto): CashMovement
    {
        if (! $session->isOpen()) {
            throw new BusinessException('Suprimento só pode ser realizado em sessão aberta.');
        }

        if ($dto->amountCents <= 0) {
            throw new BusinessException('Valor do suprimento deve ser maior que zero.');
        }

        return DB::transaction(function () use ($session, $dto): CashMovement {
            $movement = CashMovement::create([
                'store_id'                 => $session->store_id,
                'cash_register_session_id' => $session->uuid,
                'type'                     => CashMovementTypeEnum::Supply,
                'amount_cents'             => $dto->amountCents,
                'description'              => $dto->description,
                'created_by'               => Auth::id(),
            ]);

            CashSupplyCreated::dispatch($session, $movement);

            return $movement;
        });
    }
}
