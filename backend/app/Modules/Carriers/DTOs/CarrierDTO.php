<?php

declare(strict_types=1);

namespace App\Modules\Carriers\DTOs;

use Illuminate\Http\Request;

final readonly class CarrierDTO
{
    public function __construct(
        public string  $name,
        public ?string $code,
        public ?string $tradeName,
        public ?string $cnpj,
        public ?string $ie,
        public ?string $email,
        public ?string $phone,
        public ?string $deliveryMode,
        public ?string $rntrc,
        public ?string $notes,
        public bool    $isActive,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name:         $request->string('name')->toString(),
            code:         $request->input('code'),
            tradeName:    $request->input('trade_name'),
            cnpj:         $request->input('cnpj'),
            ie:           $request->input('ie'),
            email:        $request->input('email'),
            phone:        $request->input('phone'),
            deliveryMode: $request->input('delivery_mode'),
            rntrc:        $request->input('rntrc'),
            notes:        $request->input('notes'),
            isActive:     $request->boolean('is_active', true),
        );
    }
}
