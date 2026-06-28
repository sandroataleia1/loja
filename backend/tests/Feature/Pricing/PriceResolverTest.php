<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\PriceList;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductPrice;
use App\Modules\Catalog\Models\Variant;
use App\Modules\Catalog\Services\PriceResolverService;
use App\Modules\Customers\Models\Customer;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    $this->actingAsTenantUser();
});

// ── resolveListForCustomer ────────────────────────────────────────────────────

describe('resolveListForCustomer', function (): void {
    it('retorna lista vinculada ao cliente quando ele tem price_list_id', function (): void {
        $list     = PriceList::factory()->create(['tenant_id' => $this->tenant->uuid]);
        $customer = Customer::factory()->create([
            'tenant_id'     => $this->tenant->uuid,
            'price_list_id' => $list->uuid,
        ]);

        $resolver = app(PriceResolverService::class);
        $resolved = $resolver->resolveListForCustomer($this->tenant->uuid, $customer->uuid);

        expect($resolved)->toBe($list->uuid);
    });

    it('retorna lista padrão quando cliente não tem price_list_id', function (): void {
        $defaultList = PriceList::factory()->create([
            'tenant_id'  => $this->tenant->uuid,
            'is_default' => true,
            'is_active'  => true,
        ]);

        $customer = Customer::factory()->create([
            'tenant_id'     => $this->tenant->uuid,
            'price_list_id' => null,
        ]);

        $resolver = app(PriceResolverService::class);
        $resolved = $resolver->resolveListForCustomer($this->tenant->uuid, $customer->uuid);

        expect($resolved)->toBe($defaultList->uuid);
    });

    it('retorna lista padrão quando customer_id é null', function (): void {
        $defaultList = PriceList::factory()->create([
            'tenant_id'  => $this->tenant->uuid,
            'is_default' => true,
            'is_active'  => true,
        ]);

        $resolver = app(PriceResolverService::class);
        $resolved = $resolver->resolveListForCustomer($this->tenant->uuid, null);

        expect($resolved)->toBe($defaultList->uuid);
    });

    it('invalida cache de resolução de lista após PATCH price-list do cliente', function (): void {
        $list1 = PriceList::factory()->create(['tenant_id' => $this->tenant->uuid]);
        $list2 = PriceList::factory()->create(['tenant_id' => $this->tenant->uuid]);
        $customer = Customer::factory()->create([
            'tenant_id'     => $this->tenant->uuid,
            'price_list_id' => $list1->uuid,
        ]);

        $resolver = app(PriceResolverService::class);

        // Popula cache
        $before = $resolver->resolveListForCustomer($this->tenant->uuid, $customer->uuid);
        expect($before)->toBe($list1->uuid);

        // Altera via endpoint (que invalida o cache)
        $this->patchJson("/api/v1/customers/{$customer->uuid}/price-list", [
            'price_list_id' => $list2->uuid,
        ])->assertOk();

        // Cache deve ter sido invalidado — retorna nova lista
        $after = $resolver->resolveListForCustomer($this->tenant->uuid, $customer->uuid);
        expect($after)->toBe($list2->uuid);
    });
});

// ── GET /pricing/resolve ──────────────────────────────────────────────────────

describe('GET /pricing/resolve', function (): void {
    it('retorna preço correto com todos os campos', function (): void {
        $product = Product::factory()->create();
        $variant = Variant::factory()->create(['product_id' => $product->uuid, 'price_cents' => 5000]);
        $list    = PriceList::factory()->create([
            'tenant_id'  => $this->tenant->uuid,
            'is_default' => true,
            'is_active'  => true,
        ]);

        ProductPrice::create([
            'tenant_id'             => $this->tenant->uuid,
            'price_list_id'         => $list->uuid,
            'variant_id'            => $variant->uuid,
            'price_cents'           => 4500,
            'min_price_cents'       => 4000,
            'packaging_price_cents' => 42000,
            'packaging_qty'         => 10,
        ]);

        $this->getJson("/api/v1/pricing/resolve?variant_id={$variant->uuid}")
            ->assertOk()
            ->assertJsonPath('data.price_list_id', $list->uuid)
            ->assertJsonPath('data.price_cents', 4500)
            ->assertJsonPath('data.min_price_cents', 4000)
            ->assertJsonPath('data.packaging_price_cents', 42000)
            ->assertJsonPath('data.packaging_qty', '10.0000');
    });

    it('usa lista do cliente quando customer_id é informado', function (): void {
        $product  = Product::factory()->create();
        $variant  = Variant::factory()->create(['product_id' => $product->uuid, 'price_cents' => 9000]);
        $vipList  = PriceList::factory()->create(['tenant_id' => $this->tenant->uuid]);
        $customer = Customer::factory()->create([
            'tenant_id'     => $this->tenant->uuid,
            'price_list_id' => $vipList->uuid,
        ]);

        ProductPrice::create([
            'tenant_id'     => $this->tenant->uuid,
            'price_list_id' => $vipList->uuid,
            'variant_id'    => $variant->uuid,
            'price_cents'   => 7500,
        ]);

        $this->getJson("/api/v1/pricing/resolve?variant_id={$variant->uuid}&customer_id={$customer->uuid}")
            ->assertOk()
            ->assertJsonPath('data.price_list_id', $vipList->uuid)
            ->assertJsonPath('data.price_cents', 7500);
    });
});
