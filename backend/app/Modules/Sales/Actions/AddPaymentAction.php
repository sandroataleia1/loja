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
     * PAID imediatamente. Demais métodos ficam PENDING até confirmação.
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
                'code'                => $code,
                'sale_id'             => $sale->uuid,
                'payment_method_id'   => $dto->paymentMethodId,
                'payment_condition_id'=> $dto->paymentConditionId,
                'method'              => $dto->method,
                'amount_cents'        => $dto->amountCents,
                'discount_cents'      => $dto->discountCents,
                'interest_cents'      => $dto->interestCents,
                'fine_cents'          => 0,
                'installment_number'  => $dto->installmentNumber,
                'total_installments'  => $dto->totalInstallments,
                'due_date'            => $dto->dueDate,
                'status'              => $isInstant ? PaymentStatusEnum::Paid : PaymentStatusEnum::Pending,
                'external_reference'  => $dto->externalReference,
                'notes'               => $dto->notes,
                'metadata'            => $dto->metadata,
                'paid_at'             => $isInstant ? now() : null,
            ]);

            PaymentReceived::dispatch($sale, $payment);

            return $payment;
        });
    }
}
