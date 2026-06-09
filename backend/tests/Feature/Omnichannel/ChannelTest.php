<?php

declare(strict_types=1);

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Omnichannel\Enums\ChannelSyncStatusEnum;
use App\Modules\Omnichannel\Enums\ChannelTypeEnum;
use App\Modules\Omnichannel\Models\Channel;
use App\Modules\Omnichannel\Models\ChannelPrice;
use App\Modules\Omnichannel\Models\ChannelProduct;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    TenantContext::set($this->tenant->uuid);
});

afterEach(fn () => TenantContext::clear());

// ── Channel creation ──────────────────────────────────────────────────────────

it('creates a channel with the correct type', function (): void {
    $channel = Channel::create([
        'tenant_id' => $this->tenant->uuid,
        'name'      => 'Loja Instagram',
        'type'      => ChannelTypeEnum::Instagram,
        'is_active' => true,
    ]);

    expect($channel->type)->toBe(ChannelTypeEnum::Instagram)
        ->and($channel->is_active)->toBeTrue()
        ->and($channel->requiresCredentials())->toBeTrue()
        ->and($channel->isAsyncChannel())->toBeTrue();
});

it('identifies PDV channel as non-async and not requiring credentials', function (): void {
    $channel = Channel::create([
        'tenant_id' => $this->tenant->uuid,
        'name'      => 'PDV Loja Centro',
        'type'      => ChannelTypeEnum::Pdv,
        'is_active' => true,
    ]);

    expect($channel->isAsyncChannel())->toBeFalse()
        ->and($channel->requiresCredentials())->toBeFalse();
});

it('scopes active channels correctly', function (): void {
    Channel::create(['tenant_id' => $this->tenant->uuid, 'name' => 'Ativo', 'type' => ChannelTypeEnum::Instagram, 'is_active' => true]);
    Channel::create(['tenant_id' => $this->tenant->uuid, 'name' => 'Inativo', 'type' => ChannelTypeEnum::Ecommerce, 'is_active' => false]);

    expect(Channel::active()->count())->toBe(1);
});

// ── ChannelProduct publication state ─────────────────────────────────────────

it('channel_product starts as pending sync and not published', function (): void {
    $channel = Channel::create([
        'tenant_id' => $this->tenant->uuid,
        'name'      => 'WhatsApp Vendas',
        'type'      => ChannelTypeEnum::Whatsapp,
    ]);

    $productId = \Illuminate\Support\Str::uuid()->toString();

    $cp = ChannelProduct::create([
        'tenant_id'   => $this->tenant->uuid,
        'channel_id'  => $channel->uuid,
        'product_id'  => $productId,
        'sync_status' => ChannelSyncStatusEnum::Pending,
    ]);

    expect($cp->is_published)->toBeFalse()
        ->and($cp->sync_status)->toBe(ChannelSyncStatusEnum::Pending)
        ->and($cp->sync_status->needsSync())->toBeTrue();
});

it('markSynced sets sync_status to SYNCED and sets published_at', function (): void {
    $channel   = Channel::create(['tenant_id' => $this->tenant->uuid, 'name' => 'ML', 'type' => ChannelTypeEnum::Marketplace]);
    $productId = \Illuminate\Support\Str::uuid()->toString();

    $cp = ChannelProduct::create([
        'tenant_id'   => $this->tenant->uuid,
        'channel_id'  => $channel->uuid,
        'product_id'  => $productId,
        'sync_status' => ChannelSyncStatusEnum::Pending,
    ]);

    $cp->markSynced('ml-sku-123');

    expect($cp->fresh()->sync_status)->toBe(ChannelSyncStatusEnum::Synced)
        ->and($cp->fresh()->is_published)->toBeTrue()
        ->and($cp->fresh()->external_reference)->toBe('ml-sku-123')
        ->and($cp->fresh()->published_at)->not->toBeNull();
});

it('markFailed sets sync_status to FAILED and needsSync stays true', function (): void {
    $channel   = Channel::create(['tenant_id' => $this->tenant->uuid, 'name' => 'Shop', 'type' => ChannelTypeEnum::Ecommerce]);
    $productId = \Illuminate\Support\Str::uuid()->toString();

    $cp = ChannelProduct::create([
        'tenant_id'   => $this->tenant->uuid,
        'channel_id'  => $channel->uuid,
        'product_id'  => $productId,
        'sync_status' => ChannelSyncStatusEnum::Pending,
    ]);

    $cp->markFailed();

    expect($cp->fresh()->sync_status)->toBe(ChannelSyncStatusEnum::Failed)
        ->and($cp->fresh()->sync_status->needsSync())->toBeTrue();
});

it('enforces unique channel_id + product_id constraint', function (): void {
    $channel   = Channel::create(['tenant_id' => $this->tenant->uuid, 'name' => 'IG', 'type' => ChannelTypeEnum::Instagram]);
    $productId = \Illuminate\Support\Str::uuid()->toString();

    ChannelProduct::create([
        'tenant_id'  => $this->tenant->uuid,
        'channel_id' => $channel->uuid,
        'product_id' => $productId,
        'sync_status' => ChannelSyncStatusEnum::Pending,
    ]);

    expect(fn () => ChannelProduct::create([
        'tenant_id'  => $this->tenant->uuid,
        'channel_id' => $channel->uuid,
        'product_id' => $productId,
        'sync_status' => ChannelSyncStatusEnum::Pending,
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

// ── ChannelPrice ──────────────────────────────────────────────────────────────

it('creates channel price and returns effective price without promotion', function (): void {
    $channel   = Channel::create(['tenant_id' => $this->tenant->uuid, 'name' => 'Site', 'type' => ChannelTypeEnum::Ecommerce]);
    $productId = \Illuminate\Support\Str::uuid()->toString();

    $price = ChannelPrice::create([
        'tenant_id'  => $this->tenant->uuid,
        'channel_id' => $channel->uuid,
        'product_id' => $productId,
        'price'      => '199.90',
    ]);

    expect($price->effectivePrice())->toBe('199.90')
        ->and($price->isPromotionActive())->toBeFalse();
});

it('returns promotional_price as effective price when promotion is active', function (): void {
    $channel   = Channel::create(['tenant_id' => $this->tenant->uuid, 'name' => 'Site', 'type' => ChannelTypeEnum::Ecommerce]);
    $productId = \Illuminate\Support\Str::uuid()->toString();

    $price = ChannelPrice::create([
        'tenant_id'         => $this->tenant->uuid,
        'channel_id'        => $channel->uuid,
        'product_id'        => $productId,
        'price'             => '199.90',
        'promotional_price' => '149.90',
        'starts_at'         => now()->subDay(),
        'ends_at'           => now()->addDay(),
    ]);

    expect($price->isPromotionActive())->toBeTrue()
        ->and($price->effectivePrice())->toBe('149.90');
});

it('promotional price outside date range is not active', function (): void {
    $channel   = Channel::create(['tenant_id' => $this->tenant->uuid, 'name' => 'Site', 'type' => ChannelTypeEnum::Ecommerce]);
    $productId = \Illuminate\Support\Str::uuid()->toString();

    $price = ChannelPrice::create([
        'tenant_id'         => $this->tenant->uuid,
        'channel_id'        => $channel->uuid,
        'product_id'        => $productId,
        'price'             => '199.90',
        'promotional_price' => '149.90',
        'starts_at'         => now()->addDays(5),
        'ends_at'           => now()->addDays(10),
    ]);

    expect($price->isPromotionActive())->toBeFalse()
        ->and($price->effectivePrice())->toBe('199.90');
});
