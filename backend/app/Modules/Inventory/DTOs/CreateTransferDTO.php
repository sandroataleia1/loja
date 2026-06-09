<?php

declare(strict_types=1);

namespace App\Modules\Inventory\DTOs;

use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class CreateTransferDTO extends BaseDTO
{
    /**
     * @param array<array{variant_id: string, quantity: int}> $items
     */
    public function __construct(
        public string  $originStoreId,
        public string  $destinationStoreId,
        public array   $items,
        public ?string $notes,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            originStoreId:      $request->string('origin_store_id')->toString(),
            destinationStoreId: $request->string('destination_store_id')->toString(),
            items:              $request->array('items'),
            notes:              $request->string('notes')->value() ?: null,
        );
    }
}
