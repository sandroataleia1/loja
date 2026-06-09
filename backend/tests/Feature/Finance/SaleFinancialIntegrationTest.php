<?php

declare(strict_types=1);

use App\Modules\Finance\Enums\FinancialEntryStatusEnum;
use App\Modules\Finance\Models\FinancialEntry;
use App\Modules\Inventory\Models\Store;
use App\Modules\Sales\Actions\CompleteSaleAction;
use App\Modules\Sales\Enums\PaymentMethodEnum;
use App\Modules\Sales\Enums\PaymentStatusEnum;
use App\Modules\Sales\Enums\SaleStatusEnum;
use App\Modules\Sales\Models\PaymentTransaction;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleItem;

beforeEach(function (): void {
    $this->actingAsTenantUser();
    $this->store = Store::factory()->create();
});

describe('SaleCompleted → CreateFinancialEntryFromSaleAction', function (): void {
    it('cria FinancialEntry para pagamento em dinheiro com status PAID', function (): void {
        $sale = Sale::factory()->create([
            'store_id'      => $this->store->uuid,
            'status'        => SaleStatusEnum::Pending,
            'total_cents'   => 10000,
            'subtotal_cents' => 10000,
            'code'           => 'VEN000001',
        ]);
        SaleItem::factory()->create(['sale_id' => $sale->uuid, 'total_cents' => 10000, 'quantity' => 1, 'product_variant_id' => null]);
        PaymentTransaction::factory()->create([
            'sale_id'      => $sale->uuid,
            'method'       => PaymentMethodEnum::Cash,
            'amount_cents' => 10000,
            'status'       => PaymentStatusEnum::Paid,
        ]);

        app(CompleteSaleAction::class)->execute($sale);

        $entry = FinancialEntry::query()
            ->where('reference_type', 'sale')
            ->where('reference_id', $sale->uuid)
            ->first();

        expect($entry)->not->toBeNull()
            ->and($entry->status)->toBe(FinancialEntryStatusEnum::Paid)
            ->and($entry->amount_cents)->toBe(10000)
            ->and($entry->paid_at)->not->toBeNull();
    });

    it('cria FinancialEntry para cartão de crédito com status PENDING', function (): void {
        $sale = Sale::factory()->create([
            'store_id'       => $this->store->uuid,
            'status'         => SaleStatusEnum::Pending,
            'total_cents'    => 15000,
            'subtotal_cents' => 15000,
            'code'           => 'SAL000002',
        ]);
        SaleItem::factory()->create(['sale_id' => $sale->uuid, 'total_cents' => 15000, 'quantity' => 1, 'product_variant_id' => null]);
        PaymentTransaction::factory()->create([
            'sale_id'      => $sale->uuid,
            'method'       => PaymentMethodEnum::CreditCard,
            'amount_cents' => 15000,
            'status'       => PaymentStatusEnum::Paid,
        ]);

        app(CompleteSaleAction::class)->execute($sale);

        $entry = FinancialEntry::query()
            ->where('reference_type', 'sale')
            ->where('reference_id', $sale->uuid)
            ->first();

        expect($entry)->not->toBeNull()
            ->and($entry->status)->toBe(FinancialEntryStatusEnum::Pending)
            ->and($entry->paid_at)->toBeNull();
    });

    it('cria uma FinancialEntry por PaymentTransaction quando há pagamento misto', function (): void {
        $sale = Sale::factory()->create([
            'store_id'       => $this->store->uuid,
            'status'         => SaleStatusEnum::Pending,
            'total_cents'    => 20000,
            'subtotal_cents' => 20000,
            'code'           => 'SAL000003',
        ]);
        SaleItem::factory()->create(['sale_id' => $sale->uuid, 'total_cents' => 20000, 'quantity' => 1, 'product_variant_id' => null]);
        PaymentTransaction::factory()->create([
            'sale_id'      => $sale->uuid,
            'method'       => PaymentMethodEnum::Cash,
            'amount_cents' => 10000,
            'status'       => PaymentStatusEnum::Paid,
        ]);
        PaymentTransaction::factory()->create([
            'sale_id'      => $sale->uuid,
            'method'       => PaymentMethodEnum::CreditCard,
            'amount_cents' => 10000,
            'status'       => PaymentStatusEnum::Paid,
        ]);

        app(CompleteSaleAction::class)->execute($sale);

        $entries = FinancialEntry::query()
            ->where('reference_type', 'sale')
            ->where('reference_id', $sale->uuid)
            ->get();

        expect($entries)->toHaveCount(2);

        $cashEntry   = $entries->firstWhere(fn ($e) => str_contains($e->description, 'Dinheiro'));
        $cardEntry   = $entries->firstWhere(fn ($e) => str_contains($e->description, 'crédito'));

        expect($cashEntry->status)->toBe(FinancialEntryStatusEnum::Paid)
            ->and($cardEntry->status)->toBe(FinancialEntryStatusEnum::Pending);
    });

    it('PIX é liquidado instantaneamente (status PAID)', function (): void {
        $sale = Sale::factory()->create([
            'store_id'       => $this->store->uuid,
            'status'         => SaleStatusEnum::Pending,
            'total_cents'    => 5000,
            'subtotal_cents' => 5000,
            'code'           => 'SAL000004',
        ]);
        SaleItem::factory()->create(['sale_id' => $sale->uuid, 'total_cents' => 5000, 'quantity' => 1, 'product_variant_id' => null]);
        PaymentTransaction::factory()->create([
            'sale_id'      => $sale->uuid,
            'method'       => PaymentMethodEnum::Pix,
            'amount_cents' => 5000,
            'status'       => PaymentStatusEnum::Paid,
        ]);

        app(CompleteSaleAction::class)->execute($sale);

        $entry = FinancialEntry::query()
            ->where('reference_type', 'sale')
            ->where('reference_id', $sale->uuid)
            ->first();

        expect($entry->status)->toBe(FinancialEntryStatusEnum::Paid)
            ->and($entry->reconciliation_status->value)->toBe('pending');
    });

    it('lançamento tem reference_type=sale e reference_id=sale.uuid', function (): void {
        $sale = Sale::factory()->create([
            'store_id'       => $this->store->uuid,
            'status'         => SaleStatusEnum::Pending,
            'total_cents'    => 8000,
            'subtotal_cents' => 8000,
            'code'           => 'SAL000005',
        ]);
        SaleItem::factory()->create(['sale_id' => $sale->uuid, 'total_cents' => 8000, 'quantity' => 1, 'product_variant_id' => null]);
        PaymentTransaction::factory()->create([
            'sale_id'      => $sale->uuid,
            'method'       => PaymentMethodEnum::Cash,
            'amount_cents' => 8000,
            'status'       => PaymentStatusEnum::Paid,
        ]);

        app(CompleteSaleAction::class)->execute($sale);

        $this->assertDatabaseHas('financial_entries', [
            'reference_type' => 'sale',
            'reference_id'   => $sale->uuid,
            'amount_cents'   => 8000,
        ]);
    });
});
