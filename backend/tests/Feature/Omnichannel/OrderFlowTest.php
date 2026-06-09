<?php

declare(strict_types=1);

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Omnichannel\Actions\PlaceOrderAction;
use App\Modules\Omnichannel\DTOs\PlaceOrderDTO;
use App\Modules\Omnichannel\Enums\ChannelTypeEnum;
use App\Modules\Omnichannel\Enums\OrderStatusEnum;
use App\Modules\Omnichannel\Events\OrderFulfilled;
use App\Modules\Omnichannel\Events\OrderPaid;
use App\Modules\Omnichannel\Events\OrderPlaced;
use App\Modules\Omnichannel\Models\Channel;
use App\Modules\Omnichannel\Models\Order;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    $this->tenant  = Tenant::factory()->create();
    TenantContext::set($this->tenant->uuid);

    $this->channel = Channel::create([
        'tenant_id' => $this->tenant->uuid,
        'name'      => 'WhatsApp Vendas',
        'type'      => ChannelTypeEnum::Whatsapp,
        'is_active' => true,
    ]);
});

afterEach(fn () => TenantContext::clear());

// ── PlaceOrderAction ──────────────────────────────────────────────────────────

it('places an order with PENDING status and dispatches OrderPlaced event', function (): void {
    Event::fake([OrderPlaced::class]);

    $order = app(PlaceOrderAction::class)->execute(new PlaceOrderDTO(
        tenantId:    $this->tenant->uuid,
        channelId:   $this->channel->uuid,
        totalAmount: 350.00,
        metadata:    ['items' => [['product_id' => 'abc', 'qty' => 2]]],
    ));

    expect($order->status)->toBe(OrderStatusEnum::Pending)
        ->and($order->total_amount)->toBe('350.00')
        ->and($order->channel_id)->toBe($this->channel->uuid)
        ->and($order->order_number)->toStartWith('OC-');

    Event::assertDispatched(OrderPlaced::class);
});

it('generates unique order numbers', function (): void {
    $orders = collect();

    for ($i = 0; $i < 5; $i++) {
        $orders->push(app(PlaceOrderAction::class)->execute(new PlaceOrderDTO(
            tenantId:    $this->tenant->uuid,
            channelId:   $this->channel->uuid,
            totalAmount: 100.00,
        )));
    }

    expect($orders->pluck('order_number')->unique()->count())->toBe(5);
});

// ── Order status transitions ──────────────────────────────────────────────────

it('transitions order from PENDING to PAID', function (): void {
    $order = Order::create([
        'tenant_id'    => $this->tenant->uuid,
        'channel_id'   => $this->channel->uuid,
        'order_number' => 'OC-2026-00001',
        'status'       => OrderStatusEnum::Pending,
        'total_amount' => 100.00,
        'placed_at'    => now(),
    ]);

    $order->transitionTo(OrderStatusEnum::Paid);

    expect($order->fresh()->status)->toBe(OrderStatusEnum::Paid);
});

it('transitions order from PAID to FULFILLED', function (): void {
    $order = Order::create([
        'tenant_id'    => $this->tenant->uuid,
        'channel_id'   => $this->channel->uuid,
        'order_number' => 'OC-2026-00002',
        'status'       => OrderStatusEnum::Paid,
        'total_amount' => 100.00,
        'placed_at'    => now(),
    ]);

    $order->transitionTo(OrderStatusEnum::Fulfilled);

    expect($order->fresh()->status)->toBe(OrderStatusEnum::Fulfilled)
        ->and($order->fresh()->status->isFinal())->toBeTrue();
});

it('rejects invalid transition from PENDING to FULFILLED', function (): void {
    $order = Order::create([
        'tenant_id'    => $this->tenant->uuid,
        'channel_id'   => $this->channel->uuid,
        'order_number' => 'OC-2026-00003',
        'status'       => OrderStatusEnum::Pending,
        'total_amount' => 100.00,
        'placed_at'    => now(),
    ]);

    expect(fn () => $order->transitionTo(OrderStatusEnum::Fulfilled))
        ->toThrow(\App\Shared\Exceptions\BusinessException::class);
});

it('rejects any transition from a final status', function (): void {
    $order = Order::create([
        'tenant_id'    => $this->tenant->uuid,
        'channel_id'   => $this->channel->uuid,
        'order_number' => 'OC-2026-00004',
        'status'       => OrderStatusEnum::Cancelled,
        'total_amount' => 100.00,
        'placed_at'    => now(),
    ]);

    expect(fn () => $order->transitionTo(OrderStatusEnum::Paid))
        ->toThrow(\App\Shared\Exceptions\BusinessException::class);
});

it('CANCELLED is a final status and PENDING is not', function (): void {
    expect(OrderStatusEnum::Cancelled->isFinal())->toBeTrue()
        ->and(OrderStatusEnum::Fulfilled->isFinal())->toBeTrue()
        ->and(OrderStatusEnum::Pending->isFinal())->toBeFalse()
        ->and(OrderStatusEnum::Paid->isFinal())->toBeFalse();
});

// ── Order vs Sale separation ──────────────────────────────────────────────────

it('Order model uses omnichannel_orders table, not sales table', function (): void {
    expect((new Order)->getTable())->toBe('omnichannel_orders');
});

it('Order does not have fiscal or payment columns — it is not a Sale', function (): void {
    $order = Order::create([
        'tenant_id'    => $this->tenant->uuid,
        'channel_id'   => $this->channel->uuid,
        'order_number' => 'OC-2026-00005',
        'status'       => OrderStatusEnum::Pending,
        'total_amount' => 200.00,
        'placed_at'    => now(),
    ]);

    // Order only has these core fields — no sale_id, no fiscal_key, no payment_method
    expect($order->getFillable())->not->toContain('fiscal_key')
        ->and($order->getFillable())->not->toContain('payment_method')
        ->and($order->getFillable())->not->toContain('cash_register_id');
});
