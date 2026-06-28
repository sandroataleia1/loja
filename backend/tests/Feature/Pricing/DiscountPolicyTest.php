<?php

declare(strict_types=1);

use App\Core\Auth\Models\TenantUser;
use App\Core\Tenancy\Models\TenantSettings;
use App\Modules\Catalog\Models\PriceList;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductPrice;
use App\Modules\Catalog\Models\Variant;
use App\Modules\Pricing\Actions\ApplyDiscountAction;
use App\Modules\Pricing\Exceptions\DiscountExceedsLimitException;
use App\Modules\Settings\Services\DiscountPolicyService;

beforeEach(function (): void {
    $this->actingAsTenantUser();
});

// ── maxDiscountPercent — 3 níveis ─────────────────────────────────────────────

describe('DiscountPolicyService — 3 níveis', function (): void {
    it('usa limite global quando role não tem policy', function (): void {
        setGlobalLimit($this->tenant->uuid, 15.0);

        $service = app(DiscountPolicyService::class);
        $max     = $service->maxDiscountPercent($this->tenant->uuid, $this->user->uuid);

        expect($max)->toBe(15.0);
    });

    it('usa o mais restritivo entre role e global', function (): void {
        setGlobalLimit($this->tenant->uuid, 20.0);
        setRoleLimit($this->tenant->uuid, $this->user->uuid, 10.0);

        $service = app(DiscountPolicyService::class);
        $max     = $service->maxDiscountPercent($this->tenant->uuid, $this->user->uuid);

        expect($max)->toBe(10.0); // role é mais restritivo
    });

    it('usa o mais restritivo quando lista tem limite menor que role/global', function (): void {
        setGlobalLimit($this->tenant->uuid, 20.0);
        setRoleLimit($this->tenant->uuid, $this->user->uuid, 15.0);

        $list = PriceList::factory()->create([
            'tenant_id'            => $this->tenant->uuid,
            'max_discount_percent' => 5.0,
        ]);

        $service = app(DiscountPolicyService::class);
        $max     = $service->maxDiscountPercent($this->tenant->uuid, $this->user->uuid, $list->uuid);

        expect($max)->toBe(5.0); // lista é o mais restritivo
    });

    it('ignora limite da lista quando max_discount_percent = 0 (sem limite por lista)', function (): void {
        setGlobalLimit($this->tenant->uuid, 20.0);
        setRoleLimit($this->tenant->uuid, $this->user->uuid, 15.0);

        $list = PriceList::factory()->create([
            'tenant_id'            => $this->tenant->uuid,
            'max_discount_percent' => 0, // zero = sem restrição da lista
        ]);

        $service = app(DiscountPolicyService::class);
        $max     = $service->maxDiscountPercent($this->tenant->uuid, $this->user->uuid, $list->uuid);

        expect($max)->toBe(15.0); // role é mais restritivo, lista sem restrição
    });

    it('isAllowed retorna false quando desconto excede qualquer limite', function (): void {
        setGlobalLimit($this->tenant->uuid, 10.0);

        $service = app(DiscountPolicyService::class);

        expect($service->isAllowed(5.0, $this->tenant->uuid, $this->user->uuid))->toBeTrue();
        expect($service->isAllowed(10.0, $this->tenant->uuid, $this->user->uuid))->toBeTrue();
        expect($service->isAllowed(10.01, $this->tenant->uuid, $this->user->uuid))->toBeFalse();
    });
});

// ── ApplyDiscountAction ───────────────────────────────────────────────────────

describe('ApplyDiscountAction', function (): void {
    it('retorna ApplyDiscountResult correto para desconto válido', function (): void {
        setGlobalLimit($this->tenant->uuid, 20.0);

        $action = app(ApplyDiscountAction::class);
        $result = $action->execute(
            discountPercent:    10.0,
            originalPriceCents: 10000,
            tenantId:           $this->tenant->uuid,
            userId:             $this->user->uuid,
        );

        expect($result->original_price_cents)->toBe(10000);
        expect($result->discount_percent)->toBe(10.0);
        expect($result->discount_cents)->toBe(1000);
        expect($result->final_price_cents)->toBe(9000);
        expect($result->max_allowed_percent)->toBe(20.0);
    });

    it('lança DiscountExceedsLimitException quando desconto excede limite', function (): void {
        setGlobalLimit($this->tenant->uuid, 5.0);

        $action = app(ApplyDiscountAction::class);

        expect(fn () => $action->execute(
            discountPercent:    10.0,
            originalPriceCents: 10000,
            tenantId:           $this->tenant->uuid,
            userId:             $this->user->uuid,
        ))->toThrow(DiscountExceedsLimitException::class);
    });
});

// ── POST /pricing/apply-discount ──────────────────────────────────────────────

describe('POST /pricing/apply-discount', function (): void {
    it('retorna resultado correto para desconto válido', function (): void {
        setGlobalLimit($this->tenant->uuid, 20.0);

        $product = Product::factory()->create();
        $variant = Variant::factory()->create(['product_id' => $product->uuid, 'price_cents' => 10000]);
        $list    = PriceList::factory()->create([
            'tenant_id'  => $this->tenant->uuid,
            'is_default' => true,
            'is_active'  => true,
        ]);

        $this->postJson('/api/v1/pricing/apply-discount', [
            'variant_id'       => $variant->uuid,
            'discount_percent' => 10,
        ])->assertOk()
          ->assertJsonPath('data.original_price_cents', 10000)
          ->assertJsonPath('data.discount_cents', 1000)
          ->assertJsonPath('data.final_price_cents', 9000);
    });

    it('retorna 422 com detalhes quando desconto excede limite', function (): void {
        setGlobalLimit($this->tenant->uuid, 5.0);

        $product = Product::factory()->create();
        $variant = Variant::factory()->create(['product_id' => $product->uuid, 'price_cents' => 10000]);
        PriceList::factory()->create([
            'tenant_id'  => $this->tenant->uuid,
            'is_default' => true,
            'is_active'  => true,
        ]);

        $this->postJson('/api/v1/pricing/apply-discount', [
            'variant_id'       => $variant->uuid,
            'discount_percent' => 15,
        ])->assertStatus(422)
          ->assertJsonPath('errors.max_allowed_percent', 5);
    });
});

// ── Helpers ───────────────────────────────────────────────────────────────────

function setGlobalLimit(string $tenantId, float $limit): void
{
    $settings = TenantSettings::forTenant($tenantId);
    $settings->updateSection('commercial', ['default_discount_limit' => $limit]);
}

function setRoleLimit(string $tenantId, string $userId, float $limit): void
{
    $tenantUser = TenantUser::where('tenant_id', $tenantId)
        ->where('user_id', $userId)
        ->with('role')
        ->first();

    if ($tenantUser?->role) {
        $tenantUser->role->update(['policy' => ['max_discount_percent' => $limit]]);
    }
}
