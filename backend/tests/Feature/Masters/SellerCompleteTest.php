<?php

declare(strict_types=1);

use App\Modules\Sellers\Models\SellerProfile;
use App\Modules\Sellers\Models\SellerTarget;
use App\Modules\Sellers\Models\SellerCommission;
use App\Modules\Sellers\Models\SellerRegion;

beforeEach(function (): void {
    $this->actingAsTenantUser();
});

// ────────────────────────────────────────────────────────────────────────────
describe('Metas de vendedor', function (): void {
    it('cria meta para o vendedor', function (): void {
        $seller = SellerProfile::factory()->create();

        $this->postJson("/api/v1/sellers/{$seller->uuid}/targets", [
            'year'         => 2026,
            'month'        => 6,
            'target_cents' => 500000,
        ])
            ->assertCreated()
            ->assertJsonPath('data.year', 2026)
            ->assertJsonPath('data.month', 6)
            ->assertJsonPath('data.target_cents', 500000)
            ->assertJsonPath('data.achievement_percent', 0);
    });

    it('atualiza meta existente (upsert)', function (): void {
        $seller = SellerProfile::factory()->create();

        $this->postJson("/api/v1/sellers/{$seller->uuid}/targets", [
            'year'         => 2026,
            'month'        => 7,
            'target_cents' => 300000,
        ])->assertCreated();

        $this->postJson("/api/v1/sellers/{$seller->uuid}/targets", [
            'year'         => 2026,
            'month'        => 7,
            'target_cents' => 600000,
        ])->assertCreated();

        $this->assertDatabaseCount('seller_targets', 1);
        $this->assertDatabaseHas('seller_targets', ['target_cents' => 600000]);
    });

    it('SellerTarget::achievementPercent calcula corretamente', function (): void {
        $target = new SellerTarget(['target_cents' => 100000, 'achieved_cents' => 75000]);
        expect($target->achievementPercent())->toBe(75.0);
    });

    it('SellerTarget::isAchieved retorna true quando bateu a meta', function (): void {
        $target = new SellerTarget(['target_cents' => 100000, 'achieved_cents' => 100000]);
        expect($target->isAchieved())->toBeTrue();
    });

    it('lista metas do vendedor', function (): void {
        $seller = SellerProfile::factory()->create();
        SellerTarget::create([
            'tenant_id'    => $this->tenant->uuid,
            'seller_id'    => $seller->uuid,
            'year'         => 2026,
            'month'        => 1,
            'target_cents' => 200000,
        ]);

        $this->getJson("/api/v1/sellers/{$seller->uuid}/targets")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });
});

// ────────────────────────────────────────────────────────────────────────────
describe('Comissões de vendedor', function (): void {
    it('cria registro de comissão com cálculo automático', function (): void {
        $seller = SellerProfile::factory()->create();

        $this->postJson("/api/v1/sellers/{$seller->uuid}/commissions", [
            'reference_year'     => 2026,
            'reference_month'    => 6,
            'gross_amount_cents' => 100000,
            'commission_rate'    => 5.0,
        ])
            ->assertCreated()
            ->assertJsonPath('data.commission_cents', 5000)
            ->assertJsonPath('data.net_commission_cents', 5000)
            ->assertJsonPath('data.status', 'pending');
    });

    it('atualiza status de comissão para pago', function (): void {
        $seller     = SellerProfile::factory()->create();
        $commission = SellerCommission::create([
            'tenant_id'            => $this->tenant->uuid,
            'seller_id'            => $seller->uuid,
            'reference_year'       => 2026,
            'reference_month'      => 5,
            'gross_amount_cents'   => 80000,
            'commission_rate'      => 4.0,
            'commission_cents'     => 3200,
            'discount_given_cents' => 0,
            'net_commission_cents' => 3200,
            'status'               => 'pending',
        ]);

        $this->patchJson("/api/v1/sellers/{$seller->uuid}/commissions/{$commission->uuid}", [
            'status' => 'paid',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'paid');

        $this->assertDatabaseHas('seller_commissions', [
            'uuid'   => $commission->uuid,
            'status' => 'paid',
        ]);
    });
});

// ────────────────────────────────────────────────────────────────────────────
describe('Regiões de vendedor', function (): void {
    it('cria região do tipo city', function (): void {
        $seller = SellerProfile::factory()->create();

        $this->postJson("/api/v1/sellers/{$seller->uuid}/regions", [
            'region_type' => 'city',
            'value'       => 'São Paulo',
        ])
            ->assertCreated()
            ->assertJsonPath('data.region_type', 'city')
            ->assertJsonPath('data.value', 'São Paulo');
    });

    it('lista regiões', function (): void {
        $seller = SellerProfile::factory()->create();
        SellerRegion::create([
            'tenant_id'   => $this->tenant->uuid,
            'seller_id'   => $seller->uuid,
            'region_type' => 'state',
            'value'       => 'SP',
        ]);

        $this->getJson("/api/v1/sellers/{$seller->uuid}/regions")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.value', 'SP');
    });

    it('exclui região', function (): void {
        $seller = SellerProfile::factory()->create();
        $region = SellerRegion::create([
            'tenant_id'   => $this->tenant->uuid,
            'seller_id'   => $seller->uuid,
            'region_type' => 'city',
            'value'       => 'Campinas',
        ]);

        $this->deleteJson("/api/v1/sellers/{$seller->uuid}/regions/{$region->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted('seller_regions', ['uuid' => $region->uuid]);
    });
});
