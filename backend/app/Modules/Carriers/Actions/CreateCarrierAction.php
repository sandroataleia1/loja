<?php

declare(strict_types=1);

namespace App\Modules\Carriers\Actions;

use App\Modules\Carriers\DTOs\CarrierDTO;
use App\Modules\Carriers\Models\Carrier;
use App\Shared\Actions\GenerateInternalCodeAction;
use App\Shared\Enums\SequenceEntityEnum;

final readonly class CreateCarrierAction
{
    public function __construct(
        private GenerateInternalCodeAction $generateCode,
    ) {}

    public function execute(string $tenantId, CarrierDTO $dto): Carrier
    {
        $code = $dto->code ?? $this->generateCode->execute($tenantId, SequenceEntityEnum::Carrier);

        return Carrier::create([
            'tenant_id'     => $tenantId,
            'code'          => $code,
            'name'          => $dto->name,
            'trade_name'    => $dto->tradeName,
            'cnpj'          => $dto->cnpj,
            'ie'            => $dto->ie,
            'email'         => $dto->email,
            'phone'         => $dto->phone,
            'delivery_mode' => $dto->deliveryMode ?? 'own_fleet',
            'rntrc'         => $dto->rntrc,
            'notes'         => $dto->notes,
            'is_active'     => $dto->isActive,
        ]);
    }
}
