<?php

declare(strict_types=1);

namespace App\Modules\Sales\Actions;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Sales\DTOs\OpenSessionDTO;
use App\Modules\Sales\Enums\CashMovementTypeEnum;
use App\Modules\Sales\Enums\CashRegisterSessionStatusEnum;
use App\Modules\Sales\Events\CashRegisterOpened;
use App\Modules\Sales\Models\CashMovement;
use App\Modules\Sales\Models\CashRegister;
use App\Modules\Sales\Models\CashRegisterSession;
use App\Shared\Actions\GenerateInternalCodeAction;
use App\Shared\Enums\SequenceEntityEnum;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class OpenCashRegisterSessionAction
{
    public function __construct(
        private GenerateInternalCodeAction $generateCode,
    ) {}

    /**
     * Abre uma sessão de caixa.
     *
     * Se cash_register_id informado: lock no registro físico — garante 1 sessão aberta por caixa.
     * Sem cash_register_id: lock por usuário+loja (comportamento legado).
     */
    public function execute(OpenSessionDTO $dto): CashRegisterSession
    {
        return DB::transaction(function () use ($dto): CashRegisterSession {
            $tenantId = TenantContext::getIdOrFail();

            if ($dto->cashRegisterId !== null) {
                $register = CashRegister::where('uuid', $dto->cashRegisterId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($register->hasOpenSession()) {
                    throw new BusinessException(
                        "O caixa '{$register->name}' já possui uma sessão aberta."
                    );
                }
            } else {
                // Legado: 1 sessão aberta por usuário por loja
                $existing = CashRegisterSession::where('store_id', $dto->storeId)
                    ->where('user_id', Auth::id())
                    ->where('status', CashRegisterSessionStatusEnum::Open)
                    ->lockForUpdate()
                    ->first();

                if ($existing !== null) {
                    throw new BusinessException('Você já possui uma sessão de caixa aberta nesta loja.');
                }
            }

            $code = $this->generateCode->execute($tenantId, SequenceEntityEnum::CashRegister);

            $session = CashRegisterSession::create([
                'code'                 => $code,
                'store_id'             => $dto->storeId,
                'cash_register_id'     => $dto->cashRegisterId,
                'user_id'              => Auth::id(),
                'status'               => CashRegisterSessionStatusEnum::Open,
                'opening_amount_cents' => $dto->openingAmountCents,
                'notes'                => $dto->notes,
                'opened_at'            => now(),
            ]);

            // Registra movimentação de abertura
            CashMovement::create([
                'store_id'                 => $dto->storeId,
                'cash_register_session_id' => $session->uuid,
                'type'                     => CashMovementTypeEnum::Opening,
                'amount_cents'             => $dto->openingAmountCents,
                'description'              => 'Abertura de caixa',
                'created_by'               => Auth::id(),
            ]);

            CashRegisterOpened::dispatch($session);

            return $session->load(['store', 'cashRegister', 'user']);
        });
    }
}
