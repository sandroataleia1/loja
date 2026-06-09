<?php

declare(strict_types=1);

namespace App\Modules\Sales\DTOs;

use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class CreateCommissionDTO extends BaseDTO
{
    public function __construct(
        public string $userId,
        public float  $percentage,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            userId:     $request->string('user_id')->toString(),
            percentage: (float) $request->input('percentage'),
        );
    }
}
