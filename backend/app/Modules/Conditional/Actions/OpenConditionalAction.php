<?php

declare(strict_types=1);

namespace App\Modules\Conditional\Actions;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Conditional\DTOs\OpenConditionalDTO;
use App\Modules\Conditional\Enums\ConditionalStatusEnum;
use App\Modules\Conditional\Events\ConditionalCreated;
use App\Modules\Conditional\Models\Conditional;
use App\Modules\Conditional\Models\ConditionalItem;
use App\Modules\Conditional\Models\ConditionalStatusHistory;
use App\Modules\Inventory\Enums\MovementTypeEnum;
use App\Modules\Inventory\Services\InventoryService;
use App\Shared\Actions\GenerateInternalCodeAction;
use App\Shared\Enums\SequenceEntityEnum;
use App\Shared\Exceptions\BusinessException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class OpenConditionalAction
{
    public function __construct(
        private GenerateInternalCodeAction $generateCode,
        private InventoryService           $inventoryService,
    ) {}

    public function execute(OpenConditionalDTO $dto): Conditional
    {
        if (empty($dto->items)) {
            throw new BusinessException('O condicional deve ter pelo menos um item.');
        }

        $dueDate = Carbon::parse($dto->dueDate)->endOfDay();
        if (! $dueDate->isFuture()) {
            throw new BusinessException('A data de vencimento deve ser uma data futura.');
        }

        $tenantId = TenantContext::getIdOrFail();

        return DB::transaction(function () use ($dto, $tenantId, $dueDate): Conditional {
            // a. Generate code
            $code = $this->generateCode->execute($tenantId, SequenceEntityEnum::Conditional);

            // b. Calculate subtotal
            $subtotalCents = array_sum(
                array_map(
                    fn (array $item) => $item['quantity'] * $item['unit_price_cents'],
                    $dto->items,
                )
            );

            // c. Create conditional
            $conditional = Conditional::create([
                'tenant_id'      => $tenantId,
                'store_id'       => $dto->storeId,
                'customer_id'    => $dto->customerId,
                'opened_by'      => Auth::id(),
                'code'           => $code,
                'status'         => ConditionalStatusEnum::Open,
                'expires_at'     => $dueDate,
                'subtotal_cents' => $subtotalCents,
                'total_cents'    => $subtotalCents,
                'notes'          => $dto->notes,
                'total_items'    => count($dto->items),
            ]);

            // d & e. Create items + inventory movements
            foreach ($dto->items as $item) {
                ConditionalItem::create([
                    'conditional_id'   => $conditional->uuid,
                    'variant_id'       => $item['variant_id'],
                    'quantity'         => $item['quantity'],
                    'unit_price_cents' => $item['unit_price_cents'],
                    'returned_quantity' => 0,
                    'sold_quantity'     => 0,
                ]);

                $this->inventoryService->consume(
                    storeId:       $dto->storeId,
                    variantId:     $item['variant_id'],
                    quantity:      $item['quantity'],
                    referenceType: 'conditional',
                    referenceId:   $conditional->uuid,
                    type:          MovementTypeEnum::ConditionalOut,
                );
            }

            // f. Record initial status history
            ConditionalStatusHistory::create([
                'conditional_id'  => $conditional->uuid,
                'previous_status' => null,
                'current_status'  => ConditionalStatusEnum::Open->value,
                'changed_by'      => Auth::id(),
                'changed_at'      => now(),
            ]);

            // h. Dispatch event
            ConditionalCreated::dispatch($tenantId, $conditional->uuid, Auth::id());

            return $conditional->load(['items.variant', 'customer', 'store']);
        });
    }
}
