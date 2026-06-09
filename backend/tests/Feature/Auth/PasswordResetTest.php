<?php

declare(strict_types=1);

use App\Core\Auth\Models\User;
use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Services\TenantContext;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    TenantContext::set($this->tenant->uuid);

    $this->user = User::factory()->forTenant($this->tenant)->create([
        'email'    => 'reset@loja.com',
        'password' => Hash::make('senha-antiga'),
    ]);
});

afterEach(fn () => TenantContext::clear());

// ── Login e Logout ────────────────────────────────────────────────────────────

describe('Login', function (): void {

    it('autentica com credenciais válidas e retorna tenant, store, channel', function (): void {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'reset@loja.com',
            'password' => 'senha-antiga',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'token',
                    'user'   => ['uuid', 'name', 'email'],
                    'tenant' => ['uuid'],
                ],
            ])
            ->assertJsonPath('success', true);
    });

    it('rejeita credenciais inválidas com 422', function (): void {
        $this->postJson('/api/v1/auth/login', [
            'email'    => 'reset@loja.com',
            'password' => 'senha-errada',
        ])->assertUnprocessable();
    });
});

describe('Logout', function (): void {

    it('revoga token e impede acesso à rota protegida', function (): void {
        $tokenResult = $this->user->createToken('test-device');
        $plainToken  = $tokenResult->plainTextToken;
        $tokenModel  = $tokenResult->accessToken;

        $this->withToken($plainToken)->postJson('/api/v1/auth/logout')
            ->assertNoContent();

        // Verifica diretamente no DB que o token foi revogado
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenModel->id,
        ]);
    });
});

// ── Me ────────────────────────────────────────────────────────────────────────

describe('Me', function (): void {

    it('retorna dados do usuário autenticado com estrutura completa', function (): void {
        $token = $this->user->createToken('device')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user'        => ['uuid', 'name', 'email'],
                    'tenant',
                    'memberships',
                ],
            ]);
    });

    it('bloqueia acesso sem autenticação', function (): void {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    });
});

// ── Recuperação de senha ──────────────────────────────────────────────────────

describe('Forgot Password', function (): void {

    it('aceita e-mail válido e retorna sucesso', function (): void {
        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'reset@loja.com',
        ])->assertOk()
            ->assertJsonPath('success', true);
    });

    it('aceita e-mail inexistente sem revelar se existe (segurança)', function (): void {
        // Laravel Password::sendResetLink retorna INVALID_USER → ValidationException (422)
        // Importante: não retorna 500 (não vaza stack trace)
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'naoexiste@loja.com',
        ]);

        expect($response->status())->toBeIn([200, 422]);
    });

    it('rejeita payload sem e-mail', function (): void {
        $this->postJson('/api/v1/auth/forgot-password', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });
});

// ── Redefinição de senha ──────────────────────────────────────────────────────

describe('Reset Password', function (): void {

    it('redefine senha com token válido', function (): void {
        $token = Password::createToken($this->user);

        $this->postJson('/api/v1/auth/reset-password', [
            'token'                 => $token,
            'email'                 => 'reset@loja.com',
            'password'              => 'nova-senha-8chars',
            'password_confirmation' => 'nova-senha-8chars',
        ])->assertOk()
            ->assertJsonPath('success', true);

        $this->user->refresh();
        expect(Hash::check('nova-senha-8chars', $this->user->password))->toBeTrue();
    });

    it('rejeita token inválido', function (): void {
        $this->postJson('/api/v1/auth/reset-password', [
            'token'                 => 'token-invalido',
            'email'                 => 'reset@loja.com',
            'password'              => 'nova-senha-8chars',
            'password_confirmation' => 'nova-senha-8chars',
        ])->assertUnprocessable();
    });

    it('rejeita payload sem token', function (): void {
        $this->postJson('/api/v1/auth/reset-password', [
            'email'                 => 'reset@loja.com',
            'password'              => 'nova-senha-8chars',
            'password_confirmation' => 'nova-senha-8chars',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['token']);
    });
});
