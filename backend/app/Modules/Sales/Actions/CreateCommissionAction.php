<?php

declare(strict_types=1);

namespace App\Modules\Sales\Actions;

use App\Modules\Sales\DTOs\CreateCommissionDTO;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleCommission;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\DB;

final readonly class CreateCommissionAction
{
    /**
     * Registra comissão de vendedor sobre uma venda.
     * Uma venda permite apenas uma comissão por usuário.
     */
    public function execute(Sale $sale, CreateCommissionDTO $dto): SaleCommission
    {
        if ($dto->percentage <= 0 || $dto->percentage > 100) {
            throw new BusinessException('Percentual de comissão deve ser entre 0 e 100.');
        }

        if ($sale->commissions()->where('user_id', $dto->userId)->exists()) {
            throw new BusinessException('Usuário já possui comissão registrada nesta venda.');
        }

        return DB::transaction(function () use ($sale, $dto): SaleCommission {
            $amountCents = (int) round($sale->total_cents * $dto->percentage / 100);

            return SaleCommission::create([
                'sale_id'      => $sale->uuid,
                'user_id'      => $dto->userId,
                'percentage'   => $dto->percentage,
                'amount_cents' => $amountCents,
            ]);
        });
    }
}
