<?php

declare(strict_types=1);

use App\Core\Features\FeatureEnum;
use App\Core\Tenancy\Models\TenantFeature;
use App\Modules\Catalog\Jobs\CheckExpiringLotsJob;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductLot;
use Illuminate\Support\Facades\Log;

beforeEach(function (): void {
    $this->actingAsTenantUser();
});

describe('Controle de Lote', function (): void {
    it('feature desativada retorna 403', function (): void {
        // Garantir que feature NÃO está ativa (não criar TenantFeature)
        $product = Product::factory()->create(['tenant_id' => $this->tenant->uuid]);

        $response = $this->getJson("/api/v1/catalog/products/{$product->uuid}/lots");

        $response->assertStatus(403);
    });

    it('criar lote define current_qty igual a initial_qty', function (): void {
        TenantFeature::create([
            'tenant_id'  => $this->tenant->uuid,
            'feature'    => FeatureEnum::InventoryLotControl->value,
            'is_enabled' => true,
        ]);

        $product = Product::factory()->create(['tenant_id' => $this->tenant->uuid]);

        $response = $this->postJson("/api/v1/catalog/products/{$product->uuid}/lots", [
            'lot_number'  => 'LOTE-001',
            'received_at' => now()->toDateString(),
            'initial_qty' => 50,
        ]);

        $response->assertStatus(201);

        $lot = ProductLot::where('lot_number', 'LOTE-001')->first();
        expect($lot)->not->toBeNull()
            ->and((float) $lot->current_qty)->toBe(50.0)
            ->and((float) $lot->initial_qty)->toBe(50.0);
    });

    it('isExpiringSoon retorna true para lote vencendo em 15 dias', function (): void {
        $lot = ProductLot::make([
            'lot_number'   => 'TEST',
            'expiry_date'  => now()->addDays(15),
            'initial_qty'  => 1,
            'current_qty'  => 1,
        ]);

        expect($lot->isExpiringSoon(30))->toBeTrue()
            ->and($lot->isExpired())->toBeFalse();
    });

    it('isExpiringSoon retorna false para lote vencendo em 60 dias com janela de 30', function (): void {
        $lot = ProductLot::make([
            'lot_number'   => 'TEST',
            'expiry_date'  => now()->addDays(60),
            'initial_qty'  => 1,
            'current_qty'  => 1,
        ]);

        expect($lot->isExpiringSoon(30))->toBeFalse();
    });

    it('scope expiring filtra lotes que vencem nos próximos 30 dias', function (): void {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->uuid]);

        // Lote que vence em 10 dias
        ProductLot::create([
            'tenant_id'   => $this->tenant->uuid,
            'product_id'  => $product->uuid,
            'lot_number'  => 'VENCE-LOGO',
            'received_at' => now()->toDateString(),
            'expiry_date' => now()->addDays(10)->toDateString(),
            'initial_qty' => 10,
            'current_qty' => 10,
        ]);

        // Lote que vence em 90 dias
        ProductLot::create([
            'tenant_id'   => $this->tenant->uuid,
            'product_id'  => $product->uuid,
            'lot_number'  => 'VENCE-DEPOIS',
            'received_at' => now()->toDateString(),
            'expiry_date' => now()->addDays(90)->toDateString(),
            'initial_qty' => 5,
            'current_qty' => 5,
        ]);

        $expiring = ProductLot::expiring(30)->get();

        expect($expiring)->toHaveCount(1)
            ->and($expiring->first()->lot_number)->toBe('VENCE-LOGO');
    });

    it('CheckExpiringLotsJob registra log para lote expirando', function (): void {
        Log::spy();

        $product = Product::factory()->create(['tenant_id' => $this->tenant->uuid]);

        ProductLot::create([
            'tenant_id'   => $this->tenant->uuid,
            'product_id'  => $product->uuid,
            'lot_number'  => 'LOTE-VENCENDO',
            'received_at' => now()->toDateString(),
            'expiry_date' => now()->addDays(5)->toDateString(),
            'initial_qty' => 10,
            'current_qty' => 10,
        ]);

        (new CheckExpiringLotsJob())->handle();

        Log::shouldHaveReceived('info')
            ->atLeast()->once()
            ->withArgs(fn (string $message) => str_contains($message, 'LOTE-VENCENDO'));
    });
});
