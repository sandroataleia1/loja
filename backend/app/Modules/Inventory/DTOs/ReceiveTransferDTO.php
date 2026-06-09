<?php

declare(strict_types=1);

namespace App\Modules\Inventory\DTOs;

use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class ReceiveTransferDTO extends BaseDTO
{
    /**
     * @param array<array{variant_id: string, quantity_received: int}> $items
     */
    public function __construct(
        public array   $items,
        public ?string $notes,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            items: $request->array('items'),
            notes: $request->string('notes')->value() ?: null,
        );
    }
}
