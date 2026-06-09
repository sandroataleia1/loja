<?php

declare(strict_types=1);

namespace App\Modules\Sales\Actions;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Fiscal\Enums\FiscalModeEnum;
use App\Modules\Fiscal\Models\TenantFiscalSettings;
use App\Modules\Sales\DTOs\CreateSaleDTO;
use App\Modules\Sales\Enums\SaleStatusEnum;
use App\Modules\Sales\Events\SaleCreated;
use App\Modules\Sales\Models\Sale;
use App\Shared\Actions\GenerateInternalCodeAction;
use App\Shared\Enums\SequenceEntityEnum;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CreateSaleAction
{
    public function __construct(
        private GenerateInternalCodeAction $generateCode,
    ) {}

    /**
     * Cria uma venda em status DRAFT com os itens fornecidos.
     *
     * Suporta idempotência via sync_uuid: se já existir uma venda com o mesmo
     * sync_uuid no tenant, retorna a existente sem criar duplicata.
     */
    private function resolveTenantDefaultFiscalMode(): ?FiscalModeEnum
    {
        $settings = TenantFiscalSettings::where('tenant_id', TenantContext::getIdOrFail())
            ->where('is_active', true)
            ->first();

        if ($settings === null || $settings->default_fiscal_mode === null) {
            return null;
        }

        // Model cast já retorna FiscalModeEnum — não chamar tryFrom() sobre enum
        return $settings->default_fiscal_mode instanceof FiscalModeEnum
            ? $settings->default_fiscal_mode
            : FiscalModeEnum::tryFrom((string) $settings->default_fiscal_mode);
    }

    public function execute(CreateSaleDTO $dto): Sale
    {
        // Idempotência PDV: evita duplicatas em resync
        if ($dto->syncUuid !== null) {
            $existing = Sale::where('sync_uuid', $dto->syncUuid)->first();
            if ($existing !== null) {
                return $existing->load(['items', 'payments', 'discounts']);
            }
        }

        return DB::transaction(function () use ($dto): Sale {
            // Resolve fiscal_mode: DTO > tenant default > NONE
            // Congelado na criação — não derivado de config futura do tenant
            $fiscalMode = $dto->fiscalMode
                ?? $this->resolveTenantDefaultFiscalMode()
                ?? FiscalModeEnum::None;

            $subtotalCents = 0;
            $itemsData     = [];

            foreach ($dto->items as $itemDto) {
                $subtotalCents += $itemDto->totalCents();

                $itemsData[] = [
                    'product_variant_id'    => $itemDto->variantId,
                    'sku_snapshot'          => $itemDto->skuSnapshot,
                    'name_snapshot'         => $itemDto->nameSnapshot,
                    'attributes_snapshot'   => $itemDto->attributesSnapshot,
                    'quantity'              => $itemDto->quantity,
                    'unit_price_cents'      => $itemDto->unitPriceCents,
                    'cost_price_cents'      => $itemDto->costPriceCents,
                    'discount_amount_cents' => $itemDto->discountAmountCents,
                    'subtotal_cents'        => $itemDto->subtotalCents(),
                    'total_cents'           => $itemDto->totalCents(),
                ];
            }

            $code = $this->generateCode->execute(
                tenantId: TenantContext::getIdOrFail(),
                entity:   SequenceEntityEnum::Sale,
            );

            $sale = Sale::create([
                'code'                 => $code,
                'store_id'             => $dto->storeId,
                'session_id'           => $dto->sessionId,
                'customer_id'          => $dto->customerId,
                'seller_id'            => $dto->sellerId ?? Auth::id(),
                'status'               => SaleStatusEnum::Draft,
                'subtotal_cents'       => $subtotalCents,
                'discount_total_cents' => 0,
                'total_cents'          => $subtotalCents,
                'sync_uuid'            => $dto->syncUuid,
                'fiscal_mode'          => $fiscalMode,
                'sales_channel'        => $dto->salesChannel,
                'notes'                => $dto->notes,
                'metadata'             => $dto->metadata,
            ]);

            foreach ($itemsData as $data) {
                $sale->items()->create($data);
            }

            $sale->load(['items', 'payments', 'discounts', 'store', 'seller']);

            SaleCreated::dispatch($sale);

            return $sale;
        });
    }
}
