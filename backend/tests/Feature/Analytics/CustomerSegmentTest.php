<?php

declare(strict_types=1);

use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Analytics\Models\CustomerSegment;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    TenantContext::set($this->tenant->uuid);
});

afterEach(fn () => TenantContext::clear());

// ── CustomerSegment creation ──────────────────────────────────────────────────

it('creates a user-defined segment', function (): void {
    $segment = CustomerSegment::create([
        'tenant_id'   => $this->tenant->uuid,
        'name'        => 'VIP Bronze',
        'slug'        => 'vip-bronze',
        'description' => 'Clientes com gasto > R$1000',
        'is_system'   => false,
    ]);

    expect($segment->name)->toBe('VIP Bronze')
        ->and($segment->is_system)->toBeFalse();
});

it('creates a system segment', function (): void {
    $segment = CustomerSegment::create([
        'tenant_id' => $this->tenant->uuid,
        'name'      => 'Inativos',
        'slug'      => 'inativos',
        'is_system' => true,
    ]);

    expect($segment->is_system)->toBeTrue();
});

it('scopes system segments separately from user-defined', function (): void {
    CustomerSegment::create(['tenant_id' => $this->tenant->uuid, 'name' => 'Auto VIP', 'slug' => 'auto-vip', 'is_system' => true]);
    CustomerSegment::create(['tenant_id' => $this->tenant->uuid, 'name' => 'Promoção Verão', 'slug' => 'promo-verao', 'is_system' => false]);

    expect(CustomerSegment::system()->count())->toBe(1)
        ->and(CustomerSegment::userDefined()->count())->toBe(1);
});

it('enforces unique slug per tenant', function (): void {
    CustomerSegment::create(['tenant_id' => $this->tenant->uuid, 'name' => 'A', 'slug' => 'duplicado', 'is_system' => false]);

    $this->expectException(\Illuminate\Database\QueryException::class);
    CustomerSegment::create(['tenant_id' => $this->tenant->uuid, 'name' => 'B', 'slug' => 'duplicado', 'is_system' => false]);
});

// ── Membership management ─────────────────────────────────────────────────────

it('adds a member to the segment', function (): void {
    $segment    = CustomerSegment::create(['tenant_id' => $this->tenant->uuid, 'name' => 'Seg', 'slug' => 'seg', 'is_system' => false]);
    $customerId = Str::uuid()->toString();

    $segment->addMember($customerId, $this->tenant->uuid);

    expect($segment->hasMember($customerId))->toBeTrue()
        ->and($segment->members()->count())->toBe(1);

    $this->assertDatabaseHas('customer_segment_members', [
        'customer_segment_id' => $segment->uuid,
        'customer_id'         => $customerId,
    ]);
});

it('adding the same member twice does not duplicate', function (): void {
    $segment    = CustomerSegment::create(['tenant_id' => $this->tenant->uuid, 'name' => 'Seg2', 'slug' => 'seg2', 'is_system' => false]);
    $customerId = Str::uuid()->toString();

    $segment->addMember($customerId, $this->tenant->uuid);
    $segment->addMember($customerId, $this->tenant->uuid);

    expect($segment->members()->count())->toBe(1);
});

it('removes a member from the segment', function (): void {
    $segment    = CustomerSegment::create(['tenant_id' => $this->tenant->uuid, 'name' => 'Seg3', 'slug' => 'seg3', 'is_system' => false]);
    $customerId = Str::uuid()->toString();

    $segment->addMember($customerId, $this->tenant->uuid);
    $segment->removeMember($customerId);

    expect($segment->hasMember($customerId))->toBeFalse()
        ->and($segment->members()->count())->toBe(0);
});

it('soft-deletes a segment without deleting members', function (): void {
    $segment    = CustomerSegment::create(['tenant_id' => $this->tenant->uuid, 'name' => 'Del', 'slug' => 'del', 'is_system' => false]);
    $customerId = Str::uuid()->toString();
    $segment->addMember($customerId, $this->tenant->uuid);

    $segment->delete();

    expect(CustomerSegment::find($segment->uuid))->toBeNull()
        ->and(CustomerSegment::withTrashed()->find($segment->uuid))->not->toBeNull();

    $this->assertDatabaseHas('customer_segment_members', [
        'customer_segment_id' => $segment->uuid,
        'customer_id'         => $customerId,
    ]);
});
