<?php

declare(strict_types=1);

use App\Core\Auth\Models\Role;
use App\Core\Auth\Models\TenantUser;
use App\Core\Auth\Models\User;
use App\Core\Tenancy\Models\Tenant;
use App\Modules\Customers\Models\Customer;
use App\Modules\Inventory\Models\Store;
use App\Modules\Omnichannel\Models\Channel;
use App\Shared\Actions\GenerateInternalCodeAction;
use App\Shared\Enums\SequenceEntityEnum;
use Illuminate\Support\Facades\Event;

// ── Registro completo ─────────────────────────────────────────────────────────

describe('Registro', function (): void {

    it('cria tenant, usuário, loja, canal e consumidor em uma única chamada', function (): void {
        $response = $this->postJson('/api/v1/auth/register', [
            'tenant_name'           => 'Loja Exemplo',
            'name'                  => 'Administrador',
            'email'                 => 'admin@loja.com',
            'password'              => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'token',
                    'user'    => ['uuid', 'name', 'email'],
                    'tenant'  => ['uuid', 'code', 'trade_name'],
                    'store'   => ['uuid', 'code', 'name', 'type'],
                    'channel' => ['uuid', 'code', 'name', 'type'],
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.tenant.trade_name', 'Loja Exemplo')
            ->assertJsonPath('data.store.type', 'headquarters')
            ->assertJsonPath('data.channel.type', 'pdv');
    });

    it('cria o tenant com código único', function (): void {
        $this->postJson('/api/v1/auth/register', [
            'tenant_name'           => 'Empresa A',
            'name'                  => 'Admin A',
            'email'                 => 'a@empresa.com',
            'password'              => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $this->postJson('/api/v1/auth/register', [
            'tenant_name'           => 'Empresa B',
            'name'                  => 'Admin B',
            'email'                 => 'b@empresa.com',
            'password'              => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $codes = Tenant::pluck('code')->filter()->values();
        expect($codes)->toHaveCount(2)
            ->and($codes[0])->not->toBe($codes[1]);
    });

    it('cria a loja matriz com nome "Matriz" e tipo "headquarters"', function (): void {
        $this->postJson('/api/v1/auth/register', [
            'tenant_name'           => 'Matriz Test',
            'name'                  => 'Admin',
            'email'                 => 'admin@matriz.com',
            'password'              => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $tenant = Tenant::where('trade_name', 'Matriz Test')->first();
        $store  = Store::forTenant($tenant->uuid)->first();

        expect($store->name)->toBe('Matriz')
            ->and($store->type)->toBe('headquarters')
            ->and($store->is_active)->toBeTrue();
    });

    it('cria o canal PDV Principal vinculado à loja matriz', function (): void {
        $this->postJson('/api/v1/auth/register', [
            'tenant_name'           => 'Canal Test',
            'name'                  => 'Admin',
            'email'                 => 'admin@canal.com',
            'password'              => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $tenant  = Tenant::where('trade_name', 'Canal Test')->first();
        $store   = Store::forTenant($tenant->uuid)->first();
        $channel = Channel::forTenant($tenant->uuid)->first();

        expect($channel->name)->toBe('PDV Principal')
            ->and($channel->type->value)->toBe('pdv')
            ->and($channel->store_id)->toBe($store->uuid)
            ->and($channel->is_active)->toBeTrue();
    });

    it('cria o consumidor final (Consumidor Final) automaticamente', function (): void {
        $this->postJson('/api/v1/auth/register', [
            'tenant_name'           => 'Consumer Test',
            'name'                  => 'Admin',
            'email'                 => 'admin@consumer.com',
            'password'              => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $tenant   = Tenant::where('trade_name', 'Consumer Test')->first();
        $consumer = Customer::withoutTenantScope()
            ->where('tenant_id', $tenant->uuid)
            ->where('is_default_consumer', true)
            ->first();

        expect($consumer)->not->toBeNull()
            ->and($consumer->name)->toBe('Consumidor Final')
            ->and($consumer->is_active)->toBeTrue();
    });

    it('cria o usuário administrador com membership ativa', function (): void {
        $this->postJson('/api/v1/auth/register', [
            'tenant_name'           => 'Admin Test',
            'name'                  => 'Administrador',
            'email'                 => 'admin@admintest.com',
            'password'              => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $tenant     = Tenant::where('trade_name', 'Admin Test')->first();
        $user       = User::where('tenant_id', $tenant->uuid)->first();
        $membership = TenantUser::forTenant($tenant->uuid)
            ->where('user_id', $user->uuid)
            ->first();

        expect($user->name)->toBe('Administrador')
            ->and($user->is_active)->toBeTrue()
            ->and($membership)->not->toBeNull()
            ->and($membership->is_active)->toBeTrue();
    });

    it('retorna token válido que autentica na rota /me', function (): void {
        $register = $this->postJson('/api/v1/auth/register', [
            'tenant_name'           => 'Token Test',
            'name'                  => 'Admin',
            'email'                 => 'admin@token.com',
            'password'              => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $token = $register->json('data.token');

        $me = $this->withToken($token)->getJson('/api/v1/auth/me');
        $me->assertOk()->assertJsonPath('success', true);
    });

    it('rejeita senha fraca (menos de 8 caracteres)', function (): void {
        $this->postJson('/api/v1/auth/register', [
            'tenant_name'           => 'Weak Pass',
            'name'                  => 'Admin',
            'email'                 => 'admin@weak.com',
            'password'              => '123',
            'password_confirmation' => '123',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    it('rejeita e-mail sem confirmação de senha', function (): void {
        $this->postJson('/api/v1/auth/register', [
            'tenant_name'           => 'No Confirm',
            'name'                  => 'Admin',
            'email'                 => 'admin@noconfirm.com',
            'password'              => '12345678',
            'password_confirmation' => 'diferente',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    it('rejeita registro sem tenant_name', function (): void {
        $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Admin',
            'email'                 => 'admin@notenant.com',
            'password'              => '12345678',
            'password_confirmation' => '12345678',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['tenant_name']);
    });
});

// ── Geração de códigos ────────────────────────────────────────────────────────

describe('GenerateInternalCodeAction', function (): void {

    it('gera código CLI000002 para o primeiro cliente manual (CLI000001 reservado ao Consumidor Final)', function (): void {
        $tenant    = Tenant::factory()->create(); // dispara TenantCreated → cria CLI000001 (Consumidor Final)
        $generator = app(GenerateInternalCodeAction::class);

        $code = $generator->execute($tenant->uuid, SequenceEntityEnum::Customer);

        expect($code)->toBe('CLI000002');
    });

    it('incrementa código corretamente', function (): void {
        $tenant    = Tenant::factory()->create(); // CLI0001 já reservado ao Consumidor Final
        $generator = app(GenerateInternalCodeAction::class);

        $code1 = $generator->execute($tenant->uuid, SequenceEntityEnum::Customer);
        $code2 = $generator->execute($tenant->uuid, SequenceEntityEnum::Customer);
        $code3 = $generator->execute($tenant->uuid, SequenceEntityEnum::Customer);

        expect($code1)->toBe('CLI000002')
            ->and($code2)->toBe('CLI000003')
            ->and($code3)->toBe('CLI000004');
    });

    it('sequências são isoladas por tenant', function (): void {
        $tenantA   = Tenant::factory()->create();
        $tenantB   = Tenant::factory()->create();
        $generator = app(GenerateInternalCodeAction::class);

        $codeA = $generator->execute($tenantA->uuid, SequenceEntityEnum::Store);
        $codeB = $generator->execute($tenantB->uuid, SequenceEntityEnum::Store);

        expect($codeA)->toBe('LOJ000001')
            ->and($codeB)->toBe('LOJ000001');
    });

    it('gera código LOJ000001 para a primeira loja', function (): void {
        $tenant    = Tenant::factory()->create();
        $generator = app(GenerateInternalCodeAction::class);

        $code = $generator->execute($tenant->uuid, SequenceEntityEnum::Store);

        expect($code)->toBe('LOJ000001');
    });

    it('gera código CAN000001 para o primeiro canal', function (): void {
        $tenant    = Tenant::factory()->create();
        $generator = app(GenerateInternalCodeAction::class);

        $code = $generator->execute($tenant->uuid, SequenceEntityEnum::Channel);

        expect($code)->toBe('CAN000001');
    });
});
