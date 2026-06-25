<?php

declare(strict_types=1);

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Catalog\Models\AttributeGroup;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Customers\Models\Customer;

/**
 * Testes de isolamento cross-tenant para os modelos do Catálogo e Clientes.
 *
 * Padrão: tenant A cria N registros, tenant B cria M registros.
 * Consultando no contexto do tenant A, apenas os N registros de A devem
 * aparecer — nunca os M de B.
 *
 * Baseado no padrão de BrandTest.php.
 */
beforeEach(function (): void {
    $this->actingAsTenantUser();
});

// ── Category Isolation ────────────────────────────────────────────────────────

describe('Category — isolamento cross-tenant', function (): void {
    it('não retorna categorias de outro tenant', function (): void {
        $otherTenant = Tenant::factory()->create();

        // Cria 3 no tenant atual e 2 no outro
        Category::factory()->count(3)->create();
        TenantContext::set($otherTenant->uuid);
        Category::factory()->count(2)->create(['tenant_id' => $otherTenant->uuid]);
        TenantContext::set($this->tenant->uuid);

        expect(Category::count())->toBe(3);
    });

    it('lança exceção ao consultar sem contexto de tenant', function (): void {
        TenantContext::clear();

        expect(fn (): int => Category::count())
            ->toThrow(\App\Core\Tenancy\Exceptions\TenantContextMissingException::class);
    });

    it('withoutTenantScope retorna todos os registros', function (): void {
        $otherTenant = Tenant::factory()->create();

        Category::factory()->count(2)->create();
        TenantContext::set($otherTenant->uuid);
        Category::factory()->count(3)->create(['tenant_id' => $otherTenant->uuid]);
        TenantContext::clear();

        expect(Category::withoutTenantScope()->count())->toBe(5);
    });
});

// ── AttributeGroup Isolation ──────────────────────────────────────────────────

describe('AttributeGroup — isolamento cross-tenant', function (): void {
    it('não retorna grupos de atributos de outro tenant', function (): void {
        $otherTenant = Tenant::factory()->create();

        AttributeGroup::factory()->count(2)->create();
        TenantContext::set($otherTenant->uuid);
        AttributeGroup::factory()->count(4)->create(['tenant_id' => $otherTenant->uuid]);
        TenantContext::set($this->tenant->uuid);

        expect(AttributeGroup::count())->toBe(2);
    });

    it('lança exceção ao consultar sem contexto', function (): void {
        TenantContext::clear();

        expect(fn (): int => AttributeGroup::count())
            ->toThrow(\App\Core\Tenancy\Exceptions\TenantContextMissingException::class);
    });
});

// ── Product Isolation ─────────────────────────────────────────────────────────

describe('Product — isolamento cross-tenant', function (): void {
    it('não retorna produtos de outro tenant', function (): void {
        $otherTenant = Tenant::factory()->create();

        Product::factory()->count(5)->create();
        TenantContext::set($otherTenant->uuid);
        Product::factory()->count(3)->create(['tenant_id' => $otherTenant->uuid]);
        TenantContext::set($this->tenant->uuid);

        expect(Product::count())->toBe(5);
    });

    it('lança exceção ao consultar sem contexto', function (): void {
        TenantContext::clear();

        expect(fn (): int => Product::count())
            ->toThrow(\App\Core\Tenancy\Exceptions\TenantContextMissingException::class);
    });

    it('forTenant() retorna somente os produtos do tenant especificado', function (): void {
        $otherTenant = Tenant::factory()->create();

        Product::factory()->count(2)->create();
        TenantContext::set($otherTenant->uuid);
        Product::factory()->count(7)->create(['tenant_id' => $otherTenant->uuid]);
        TenantContext::clear();

        expect(Product::forTenant($otherTenant->uuid)->count())->toBe(7);
        expect(Product::forTenant($this->tenant->uuid)->count())->toBe(2);
    });
});

// ── Customer Isolation ────────────────────────────────────────────────────────

describe('Customer — isolamento cross-tenant', function (): void {
    it('não retorna clientes de outro tenant', function (): void {
        $otherTenant = Tenant::factory()->create();

        Customer::factory()->count(4)->create();
        TenantContext::set($otherTenant->uuid);
        Customer::factory()->count(2)->create(['tenant_id' => $otherTenant->uuid]);
        TenantContext::set($this->tenant->uuid);

        expect(Customer::count())->toBe(4);
    });

    it('lança exceção ao consultar sem contexto', function (): void {
        TenantContext::clear();

        expect(fn (): int => Customer::count())
            ->toThrow(\App\Core\Tenancy\Exceptions\TenantContextMissingException::class);
    });
});
