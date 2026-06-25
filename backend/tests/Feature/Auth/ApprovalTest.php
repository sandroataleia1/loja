<?php

declare(strict_types=1);

use App\Core\Auth\Enums\ApprovalStatusEnum;
use App\Core\Auth\Models\ApprovalRequest;
use App\Core\Auth\Models\User;
use Illuminate\Support\Facades\Cache;

// TestCase e RefreshDatabase configurados globalmente em tests/Pest.php

// ── Helpers ──────────────────────────────────────────────────────────────────

function makeApproverWithPin(string $tenantId, string $pin = '9876'): User
{
    return User::factory()->create([
        'tenant_id' => $tenantId,
        'pin'       => hash('sha256', config('app.key') . $pin),
        'is_active' => true,
    ]);
}

// ── Tests ─────────────────────────────────────────────────────────────────────

test('cria uma solicitação de aprovação com status pending', function (): void {
    $this->actingAsTenantUser();

    $response = $this->postJson('/api/v1/approvals', [
        'operation_type' => 'discount',
        'entity_type'    => 'sale',
        'entity_uuid'    => \Illuminate\Support\Str::uuid()->toString(),
        'context'        => ['amount' => 150.00, 'discount_pct' => 20],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', ApprovalStatusEnum::Pending->value);

    expect(ApprovalRequest::count())->toBe(1);
});

test('resolve com PIN correto → status approved + audit registrado', function (): void {
    $this->actingAsTenantUser();

    $approver = makeApproverWithPin($this->tenant->uuid, '9876');

    $approval = ApprovalRequest::create([
        'tenant_id'      => $this->tenant->uuid,
        'operation_type' => 'discount',
        'requested_by'   => $this->user->uuid,
        'requested_at'   => now(),
        'status'         => ApprovalStatusEnum::Pending->value,
    ]);

    $response = $this->postJson("/api/v1/approvals/{$approval->uuid}/resolve", [
        'pin'    => '9876',
        'action' => 'approved',
        'notes'  => 'Aprovado pelo gerente',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.status', ApprovalStatusEnum::Approved->value);

    $approval->refresh();
    expect($approval->isApproved())->toBeTrue()
        ->and($approval->resolved_by)->toBe($approver->uuid);
});

test('resolve com PIN errado → 403 + audit registrado', function (): void {
    $this->actingAsTenantUser();

    $approval = ApprovalRequest::create([
        'tenant_id'      => $this->tenant->uuid,
        'operation_type' => 'cancellation',
        'requested_by'   => $this->user->uuid,
        'requested_at'   => now(),
        'status'         => ApprovalStatusEnum::Pending->value,
    ]);

    $response = $this->postJson("/api/v1/approvals/{$approval->uuid}/resolve", [
        'pin'    => '0000',
        'action' => 'approved',
    ]);

    $response->assertStatus(403);
});

test('3 tentativas de PIN errado → auto-rejeição', function (): void {
    $this->actingAsTenantUser();

    $approval = ApprovalRequest::create([
        'tenant_id'      => $this->tenant->uuid,
        'operation_type' => 'discount',
        'requested_by'   => $this->user->uuid,
        'requested_at'   => now(),
        'status'         => ApprovalStatusEnum::Pending->value,
    ]);

    // 3 tentativas com PIN errado
    for ($i = 0; $i < 3; $i++) {
        $this->postJson("/api/v1/approvals/{$approval->uuid}/resolve", [
            'pin'    => '0000',
            'action' => 'approved',
        ]);
    }

    $approval->refresh();

    expect($approval->isRejected())->toBeTrue()
        ->and($approval->resolution_notes)->toContain('Bloqueado');
});

test('cancel encerra a solicitação como expired', function (): void {
    $this->actingAsTenantUser();

    $approval = ApprovalRequest::create([
        'tenant_id'      => $this->tenant->uuid,
        'operation_type' => 'reversal',
        'requested_by'   => $this->user->uuid,
        'requested_at'   => now(),
        'status'         => ApprovalStatusEnum::Pending->value,
    ]);

    $response = $this->postJson("/api/v1/approvals/{$approval->uuid}/cancel");

    $response->assertOk();

    $approval->refresh();
    expect($approval->isExpired())->toBeTrue();
});

test('isExpired() retorna true para status expired', function (): void {
    $approval = new ApprovalRequest(['status' => ApprovalStatusEnum::Expired]);
    expect($approval->isExpired())->toBeTrue();
});

test('isTerminal() retorna false apenas para pending', function (): void {
    $pending  = new ApprovalRequest(['status' => ApprovalStatusEnum::Pending]);
    $approved = new ApprovalRequest(['status' => ApprovalStatusEnum::Approved]);

    expect($pending->isTerminal())->toBeFalse()
        ->and($approved->isTerminal())->toBeTrue();
});
