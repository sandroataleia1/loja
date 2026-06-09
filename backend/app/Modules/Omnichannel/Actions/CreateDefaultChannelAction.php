<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Actions;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Inventory\Models\Store;
use App\Modules\Omnichannel\Enums\ChannelTypeEnum;
use App\Modules\Omnichannel\Events\ChannelCreated;
use App\Modules\Omnichannel\Models\Channel;
use App\Shared\Actions\GenerateInternalCodeAction;
use App\Shared\Enums\SequenceEntityEnum;

/**
 * Cria o canal PDV padrão vinculado à loja matriz do tenant.
 */
final readonly class CreateDefaultChannelAction
{
    public function __construct(
        private GenerateInternalCodeAction $generateCode,
    ) {}

    public function execute(string $tenantId, Store $store): Channel
    {
        $previousTenantId = TenantContext::getId();
        TenantContext::set($tenantId);

        try {
            $code = $this->generateCode->execute($tenantId, SequenceEntityEnum::Channel);
        } finally {
            if ($previousTenantId !== null) {
                TenantContext::set($previousTenantId);
            } else {
                TenantContext::clear();
            }
        }

        $channel = Channel::withoutTenantScope()->create([
            'tenant_id' => $tenantId,
            'store_id'  => $store->uuid,
            'code'      => $code,
            'name'      => 'PDV Principal',
            'type'      => ChannelTypeEnum::Pdv,
            'is_active' => true,
        ]);

        ChannelCreated::dispatch($channel);

        return $channel;
    }
}
