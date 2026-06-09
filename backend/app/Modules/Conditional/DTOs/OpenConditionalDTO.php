<?php

declare(strict_types=1);

namespace App\Modules\Conditional\DTOs;

use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class OpenConditionalDTO extends BaseDTO
{
    public function __construct(
        public string  $storeId,
        public string  $customerId,
        public string  $dueDate,
        public array   $items,
        public ?string $notes,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            storeId:    $request->string('store_id')->toString(),
            customerId: $request->string('customer_id')->toString(),
            dueDate:    $request->string('due_date')->toString(),
            items:      $request->array('items'),
            notes:      $request->string('notes')->value() ?: null,
        );
    }
}
