<?php

declare(strict_types=1);

namespace App\Modules\Sales\Actions;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Sales\DTOs\AddPaymentDTO;
use App\Modules\Sales\Enums\PaymentStatusEnum;
use App\Modules\Sales\Events\PaymentReceived;
use App\Modules\Sales\Models\PaymentTransaction;
use App\Modules\Sales\Models\Sale;
use App\Shared\Actions\GenerateInternalCodeAction;
use App\Shared\Enums\SequenceEntityEnum;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\DB;

final readonly class AddPaymentAction
{
    public function __construct(
        private GenerateInternalCodeAction $generateCode,
    ) {}

    /**
     * Adiciona um pagamento à venda.
     *
     * Métodos instantâneos (cash, pix, store_credit, voucher) são marcados como
     * PAID imediatamente. Cartões ficam PENDING até confirmação da adquirente.
     */
    public function execute(Sale $sale, AddPaymentDTO $dto): PaymentTransaction
    {
        if (! $sale->status->isEditable()) {
            throw new BusinessException(
                "Não é possível adicionar pagamento a uma venda no status '{$sale->status->label()}'."
            );
        }

        return DB::transaction(function () use ($sale, $dto): PaymentTransaction {
            $isInstant = $dto->method->isInstant();

            $code = $this->generateCode->execute(
                tenantId: TenantContext::getIdOrFail(),
                entity:   SequenceEntityEnum::Payment,
            );

            $payment = PaymentTransaction::create([
                'code'               => $code,
                'sale_id'            => $sale->uuid,
                'method'             => $dto->method,
                'amount_cents'       => $dto->amountCents,
                'status'             => $isInstant ? PaymentStatusEnum::Paid : PaymentStatusEnum::Pending,
                'external_reference' => $dto->externalReference,
                'notes'              => $dto->notes,
                'metadata'           => $dto->metadata,
                'paid_at'            => $isInstant ? now() : null,
            ]);

            PaymentReceived::dispatch($sale, $payment);

            return $payment;
        });
    }
}
