<?php

declare(strict_types=1);

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Omnichannel\Actions\PublishProductToChannelAction;
use App\Modules\Omnichannel\Actions\UnpublishProductFromChannelAction;
use App\Modules\Omnichannel\Actions\UpdateChannelPriceAction;
use App\Modules\Omnichannel\DTOs\PublishProductDTO;
use App\Modules\Omnichannel\DTOs\UpdateChannelPriceDTO;
use App\Modules\Omnichannel\Enums\ChannelSyncStatusEnum;
use App\Modules\Omnichannel\Enums\ChannelTypeEnum;
use App\Modules\Omnichannel\Events\ProductPublishedToChannel;
use App\Modules\Omnichannel\Events\ProductUnpublishedFromChannel;
use App\Modules\Omnichannel\Jobs\PublishProductToChannelJob;
use App\Modules\Omnichannel\Models\Channel;
use App\Modules\Omnichannel\Models\ChannelPrice;
use App\Modules\Omnichannel\Models\ChannelProduct;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    TenantContext::set($this->tenant->uuid);
});

afterEach(fn () => TenantContext::clear());

// ── PublishProductToChannelAction ─────────────────────────────────────────────

it('creates a ChannelProduct record with PENDING status when publishing to async channel', function (): void {
    Queue::fake();
    Event::fake([ProductPublishedToChannel::class]);

    $channel   = Channel::create(['tenant_id' => $this->tenant->uuid, 'name' => 'IG', 'type' => ChannelTypeEnum::Instagram, 'is_active' => true]);
    $productId = Str::uuid()->toString();

    $cp = app(PublishProductToChannelAction::class)->execute(new PublishProductDTO(
        tenantId:  $this->tenant->uuid,
        channelId: $channel->uuid,
        productId: $productId,
    ));

    expect($cp->sync_status)->toBe(ChannelSyncStatusEnum::Pending)
        ->and($cp->is_published)->toBeFalse()
        ->and($cp->tenant_id)->toBe($this->tenant->uuid);

    Queue::assertPushed(PublishProductToChannelJob::class);
    Event::assertDispatched(ProductPublishedToChannel::class);
});

it('marks PDV channel products as synced immediately without queuing a job', function (): void {
    Queue::fake();

    $channel   = Channel::create(['tenant_id' => $this->tenant->uuid, 'name' => 'PDV', 'type' => ChannelTypeEnum::Pdv, 'is_active' => true]);
    $productId = Str::uuid()->toString();

    $cp = app(PublishProductToChannelAction::class)->execute(new PublishProductDTO(
        tenantId:  $this->tenant->uuid,
        channelId: $channel->uuid,
        productId: $productId,
    ));

    expect($cp->sync_status)->toBe(ChannelSyncStatusEnum::Synced)
        ->and($cp->is_published)->toBeTrue();

    Queue::assertNotPushed(PublishProductToChannelJob::class);
});

it('is idempotent — re-publishing resets to PENDING and re-queues the job', function (): void {
    Queue::fake();

    $channel   = Channel::create(['tenant_id' => $this->tenant->uuid, 'name' => 'ML', 'type' => ChannelTypeEnum::Marketplace, 'is_active' => true]);
    $productId = Str::uuid()->toString();

    $dto = new PublishProductDTO(tenantId: $this->tenant->uuid, channelId: $channel->uuid, productId: $productId);

    app(PublishProductToChannelAction::class)->execute($dto);
    app(PublishProductToChannelAction::class)->execute($dto);

    expect(ChannelProduct::where('channel_id', $channel->uuid)->where('product_id', $productId)->count())->toBe(1);
    Queue::assertPushed(PublishProductToChannelJob::class, 2);
});

it('rejects publishing to an inactive channel', function (): void {
    $channel   = Channel::create(['tenant_id' => $this->tenant->uuid, 'name' => 'Inactive', 'type' => ChannelTypeEnum::Ecommerce, 'is_active' => false]);
    $productId = Str::uuid()->toString();

    expect(fn () => app(PublishProductToChannelAction::class)->execute(new PublishProductDTO(
        tenantId:  $this->tenant->uuid,
        channelId: $channel->uuid,
        productId: $productId,
    )))->toThrow(\App\Shared\Exceptions\BusinessException::class);
});

// ── UnpublishProductFromChannelAction ─────────────────────────────────────────

it('unpublishes a product from a channel', function (): void {
    Event::fake([ProductUnpublishedFromChannel::class]);

    $channel   = Channel::create(['tenant_id' => $this->tenant->uuid, 'name' => 'IG', 'type' => ChannelTypeEnum::Instagram, 'is_active' => true]);
    $productId = Str::uuid()->toString();

    ChannelProduct::create([
        'tenant_id'   => $this->tenant->uuid,
        'channel_id'  => $channel->uuid,
        'product_id'  => $productId,
        'is_published' => true,
        'sync_status' => ChannelSyncStatusEnum::Synced,
    ]);

    app(UnpublishProductFromChannelAction::class)->execute($channel->uuid, $productId, $this->tenant->uuid);

    expect(ChannelProduct::where('channel_id', $channel->uuid)->where('product_id', $productId)->first()->is_published)
        ->toBeFalse();

    Event::assertDispatched(ProductUnpublishedFromChannel::class);
});

// ── UpdateChannelPriceAction ──────────────────────────────────────────────────

it('creates a channel price upsert', function (): void {
    $channel   = Channel::create(['tenant_id' => $this->tenant->uuid, 'name' => 'Site', 'type' => ChannelTypeEnum::Ecommerce, 'is_active' => true]);
    $productId = Str::uuid()->toString();

    $price = app(UpdateChannelPriceAction::class)->execute(new UpdateChannelPriceDTO(
        tenantId:  $this->tenant->uuid,
        channelId: $channel->uuid,
        productId: $productId,
        price:     199.90,
    ));

    expect($price->price)->toBe('199.90')
        ->and($price->channel_id)->toBe($channel->uuid);
});

it('upserts existing channel price on second call', function (): void {
    $channel   = Channel::create(['tenant_id' => $this->tenant->uuid, 'name' => 'Site', 'type' => ChannelTypeEnum::Ecommerce, 'is_active' => true]);
    $productId = Str::uuid()->toString();

    $dto = new UpdateChannelPriceDTO(tenantId: $this->tenant->uuid, channelId: $channel->uuid, productId: $productId, price: 199.90);

    app(UpdateChannelPriceAction::class)->execute($dto);
    app(UpdateChannelPriceAction::class)->execute(new UpdateChannelPriceDTO(
        tenantId:  $this->tenant->uuid,
        channelId: $channel->uuid,
        productId: $productId,
        price:     249.90,
    ));

    expect(ChannelPrice::where('channel_id', $channel->uuid)->where('product_id', $productId)->count())->toBe(1);
    expect(ChannelPrice::where('channel_id', $channel->uuid)->where('product_id', $productId)->first()->price)->toBe('249.90');
});
