<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Actions;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Inventory\DTOs\CreateTransferDTO;
use App\Modules\Inventory\Enums\TransferStatusEnum;
use App\Modules\Inventory\Models\StockTransfer;
use App\Modules\Inventory\Models\StockTransferItem;
use App\Shared\Actions\GenerateInternalCodeAction;
use App\Shared\Enums\SequenceEntityEnum;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CreateTransferAction
{
    public function __construct(
        private GenerateInternalCodeAction $generateCode,
    ) {}

    public function execute(CreateTransferDTO $dto): StockTransfer
    {
        if ($dto->originStoreId === $dto->destinationStoreId) {
            throw new BusinessException('A loja de origem e destino não podem ser a mesma.');
        }

        return DB::transaction(function () use ($dto): StockTransfer {
            $code = $this->generateCode->execute(
                tenantId: TenantContext::getIdOrFail(),
                entity:   SequenceEntityEnum::Transfer,
            );

            $transfer = StockTransfer::create([
                'code'                 => $code,
                'origin_store_id'      => $dto->originStoreId,
                'destination_store_id' => $dto->destinationStoreId,
                'status'               => TransferStatusEnum::Pending,
                'notes'                => $dto->notes,
                'created_by'           => Auth::id(),
            ]);

            foreach ($dto->items as $item) {
                StockTransferItem::create([
                    'transfer_id'        => $transfer->uuid,
                    'variant_id'         => $item['variant_id'],
                    'quantity_requested' => $item['quantity'],
                ]);
            }

            return $transfer->load(['originStore', 'destinationStore', 'items.variant']);
        });
    }
}
