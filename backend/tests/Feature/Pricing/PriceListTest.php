<?php

declare(strict_types=1);

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Catalog\Models\PriceList;
use App\Modules\Catalog\Models\ProductPrice;
use App\Modules\Catalog\Models\ProductPriceHistory;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Variant;
use App\Modules\Catalog\Services\PriceResolverService;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    $this->actingAsTenantUser();
});

// ── is_default unicidade ──────────────────────────────────────────────────────

describe('is_default unicidade', function (): void {
    it('marcar lista B como padrão remove o padrão da lista A', function (): void {
        $listA = PriceList::factory()->create(['is_default' => true]);
        $listB = PriceList::factory()->create(['is_default' => false]);

        $this->putJson("/api/v1/catalog/price-lists/{$listB->uuid}", ['is_default' => true])
            ->assertOk()
            ->assertJsonPath('data.is_default', true);

        expect($listA->refresh()->is_default)->toBeFalse();
        expect($listB->refresh()->is_default)->toBeTrue();
    });

    it('ao criar lista com is_default=true remove o padrão anterior', function (): void {
        $listA = PriceList::factory()->create(['is_default' => true]);

        $this->postJson('/api/v1/catalog/price-lists', [
            'name'       => 'Nova Padrão',
            'code'       => 'NOVA',
            'type'       => 'retail',
            'is_default' => true,
        ])->assertStatus(201);

        expect($listA->refresh()->is_default)->toBeFalse();
    });

    it('não permite criar duas listas padrão no mesmo tenant', function (): void {
        PriceList::factory()->create(['is_default' => true]);

        // Via DB direto: o índice parcial deve impedir
        $this->expectException(\Illuminate\Database\QueryException::class);

        PriceList::create([
            'name'       => 'Duplicada',
            'code'       => 'DUP',
            'type'       => 'wholesale',
            'is_default' => true,
        ]);
    });
});

// ── Bloqueio de delete em lista padrão ───────────────────────────────────────

describe('delete lista padrão', function (): void {
    it('retorna 422 ao tentar deletar lista padrão', function (): void {
        $list = PriceList::factory()->create(['is_default' => true]);

        $this->deleteJson("/api/v1/catalog/price-lists/{$list->uuid}")
            ->assertStatus(422)
            ->assertJsonPath('message', fn ($msg) => str_contains($msg, 'padrão'));
    });

    it('permite deletar lista não-padrão normalmente', function (): void {
        $list = PriceList::factory()->create(['is_default' => false]);

        $this->deleteJson("/api/v1/catalog/price-lists/{$list->uuid}")
            ->assertStatus(204);

        $this->assertSoftDeleted('price_lists', ['uuid' => $list->uuid]);
    });
});

// ── upsertPrices invalida cache ───────────────────────────────────────────────

describe('upsertPrices + cache', function (): void {
    it('invalida cache após atualizar preço via upsert', function (): void {
        $product = Product::factory()->create();
        $variant = Variant::factory()->create(['product_id' => $product->uuid, 'price_cents' => 5000]);
        $list    = PriceList::factory()->create(['tenant_id' => $this->tenant->uuid]);

        $resolver = app(PriceResolverService::class);

        // Popula cache com preço inicial
        $initial = $resolver->resolve($this->tenant->uuid, $variant->uuid, $list->uuid);
        expect($initial)->toBe(5000); // fallback base

        // Upsert com novo preço
        $this->postJson("/api/v1/catalog/price-lists/{$list->uuid}/prices", [
            'prices' => [[
                'variant_id'  => $variant->uuid,
                'price_cents' => 4200,
            ]],
        ])->assertOk();

        // Cache deve ter sido invalidado — resolver retorna novo valor
        $updated = $resolver->resolve($this->tenant->uuid, $variant->uuid, $list->uuid);
        expect($updated)->toBe(4200);
    });

    it('registra histórico manual no upsert em lote', function (): void {
        $product = Product::factory()->create();
        $variant = Variant::factory()->create(['product_id' => $product->uuid]);
        $list    = PriceList::factory()->create(['tenant_id' => $this->tenant->uuid]);

        $this->postJson("/api/v1/catalog/price-lists/{$list->uuid}/prices", [
            'prices' => [[
                'variant_id'  => $variant->uuid,
                'price_cents' => 3000,
                'reason'      => 'Cadastro teste',
            ]],
        ])->assertOk();

        $history = ProductPriceHistory::where('variant_id', $variant->uuid)->first();

        expect($history)->not->toBeNull();
        expect($history->new_price_cents)->toBe(3000);
        expect($history->old_price_cents)->toBeNull(); // primeiro cadastro
        expect($history->reason)->toBe('Cadastro inicial');
    });

    it('registra variação de preço no histórico ao atualizar via upsert', function (): void {
        $product = Product::factory()->create();
        $variant = Variant::factory()->create(['product_id' => $product->uuid]);
        $list    = PriceList::factory()->create(['tenant_id' => $this->tenant->uuid]);

        // Cria preço inicial
        ProductPrice::create([
            'tenant_id'     => $this->tenant->uuid,
            'price_list_id' => $list->uuid,
            'variant_id'    => $variant->uuid,
            'price_cents'   => 2000,
        ]);

        // Atualiza via upsert
        $this->postJson("/api/v1/catalog/price-lists/{$list->uuid}/prices", [
            'prices' => [[
                'variant_id'  => $variant->uuid,
                'price_cents' => 2500,
                'reason'      => 'Reajuste',
            ]],
        ])->assertOk();

        $history = ProductPriceHistory::where('variant_id', $variant->uuid)
            ->orderBy('changed_at', 'desc')
            ->first();

        expect($history->old_price_cents)->toBe(2000);
        expect($history->new_price_cents)->toBe(2500);
        expect($history->reason)->toBe('Reajuste');
    });
});

// ── Isolamento cross-tenant ───────────────────────────────────────────────────

describe('isolamento cross-tenant', function (): void {
    it('tenant A não vê price_lists do tenant B no index()', function (): void {
        // 2 listas no tenant A (o tenant atual)
        PriceList::factory()->count(2)->create();

        // 3 listas no tenant B
        $tenantB = Tenant::factory()->create();
        TenantContext::set($tenantB->uuid);
        PriceList::factory()->count(3)->create(['tenant_id' => $tenantB->uuid]);
        TenantContext::set($this->tenant->uuid);

        $response = $this->getJson('/api/v1/catalog/price-lists');

        $response->assertOk();
        expect(count($response->json('data')))->toBe(2);
    });
});

// ── index com parâmetros de admin ─────────────────────────────────────────────

describe('index() com parâmetros admin', function (): void {
    it('retorna apenas ativas por padrão', function (): void {
        PriceList::factory()->create(['is_active' => true]);
        PriceList::factory()->create(['is_active' => false]);

        $this->getJson('/api/v1/catalog/price-lists')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('retorna inativas quando include_inactive=true', function (): void {
        PriceList::factory()->create(['is_active' => true]);
        PriceList::factory()->create(['is_active' => false]);

        $this->getJson('/api/v1/catalog/price-lists?include_inactive=true')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('retorna expiradas quando include_expired=true', function (): void {
        PriceList::factory()->create(['valid_to' => now()->subDay()]);
        PriceList::factory()->create(['valid_to' => now()->addDay()]);

        $this->getJson('/api/v1/catalog/price-lists?include_expired=true')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });
});
