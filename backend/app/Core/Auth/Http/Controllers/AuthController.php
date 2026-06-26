<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Controllers;

use App\Core\Auth\Actions\LoginAction;
use App\Core\Auth\Actions\LogoutAction;
use App\Core\Auth\Actions\RegisterAction;
use App\Core\Auth\DTOs\LoginDTO;
use App\Core\Auth\DTOs\RegisterDTO;
use App\Core\Auth\Events\PasswordResetCompleted;
use App\Core\Auth\Events\PasswordResetRequested;
use App\Core\Auth\Http\Requests\ForgotPasswordRequest;
use App\Core\Auth\Http\Requests\LoginRequest;
use App\Core\Auth\Http\Requests\RegisterRequest;
use App\Core\Auth\Http\Requests\ResetPasswordRequest;
use App\Core\Auth\Http\Resources\TenantUserResource;
use App\Core\Auth\Http\Resources\UserResource;
use App\Core\Auth\Models\User;
use App\Core\Tenancy\Http\Resources\TenantResource;
use App\Core\Tenancy\Models\Tenant;
use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Inventory\Http\Resources\StoreResource;
use App\Modules\Inventory\Models\Store;
use App\Modules\Omnichannel\Http\Resources\ChannelResource;
use App\Modules\Omnichannel\Models\Channel;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

/**
 * @group Authentication
 *
 * Registro, login, logout, recuperação de senha e perfil do usuário autenticado.
 */
final class AuthController extends Controller
{
    use HasApiResponse;

    /**
     * Registrar nova empresa
     *
     * Cria tenant, usuário administrador, loja matriz, canal PDV e consumidor final
     * dentro de uma única transação. A empresa estará pronta para operar imediatamente.
     *
     * @unauthenticated
     *
     * @bodyParam tenant_name string required Nome da empresa. Example: Loja Exemplo
     * @bodyParam name string required Nome do administrador. Example: Administrador
     * @bodyParam email string required E-mail do administrador. Example: admin@loja.com
     * @bodyParam password string required Senha (mínimo 8 caracteres). Example: 12345678
     * @bodyParam password_confirmation string required Confirmação da senha. Example: 12345678
     * @bodyParam legal_name string Razão social. Example: Loja Exemplo LTDA
     * @bodyParam document string CNPJ ou CPF. Example: 00.000.000/0001-00
     * @bodyParam phone string Telefone. Example: (11) 99999-9999
     *
     * @response 201 scenario="Sucesso" {"success":true,"data":{"token":"...","user":{},"tenant":{},"store":{},"channel":{}}}
     */
    public function register(RegisterRequest $request, RegisterAction $action): JsonResponse
    {
        $result = $action->execute(RegisterDTO::fromRequest($request));

        return $this->created([
            'token'   => $result['token'],
            'user'    => new UserResource($result['user']),
            'tenant'  => new TenantResource($result['tenant']),
            'store'   => new StoreResource($result['store']),
            'channel' => new ChannelResource($result['channel']),
        ], 'Empresa criada com sucesso.');
    }

    /**
     * Login
     *
     * Autentica o usuário e retorna token Sanctum com contexto completo do tenant.
     *
     * @unauthenticated
     *
     * @bodyParam email string required E-mail. Example: admin@loja.com
     * @bodyParam password string required Senha. Example: 12345678
     * @bodyParam device_name string Nome do dispositivo para o token. Example: PDV-001
     *
     * @response 200 scenario="Sucesso" {"success":true,"data":{"token":"...","user":{},"tenant":{},"store":{},"channel":{}}}
     */
    public function login(LoginRequest $request, LoginAction $action): JsonResponse
    {
        $result = $action->execute(LoginDTO::fromRequest($request));

        /** @var User $user */
        $user   = $result['user'];
        $tenant = Tenant::where('uuid', $user->tenant_id)->first();
        $store  = $tenant ? Store::forTenant($tenant->uuid)->where('type', 'headquarters')->first() : null;
        $channel = $tenant ? Channel::forTenant($tenant->uuid)->where('type', 'pdv')->first() : null;

        return $this->success([
            'token'   => $result['token'],
            'user'    => new UserResource($user),
            'tenant'  => $tenant ? new TenantResource($tenant) : null,
            'store'   => $store ? new StoreResource($store) : null,
            'channel' => $channel ? new ChannelResource($channel) : null,
        ]);
    }

    /**
     * Logout
     *
     * Revoga o token atual do usuário autenticado.
     *
     * @authenticated
     *
     * @response 204
     */
    public function logout(Request $request, LogoutAction $action): JsonResponse
    {
        $action->execute($request->user());

        return $this->noContent();
    }

    /**
     * Usuário autenticado
     *
     * Retorna o perfil completo do usuário, tenant, memberships e contexto operacional.
     *
     * @authenticated
     *
     * @response 200 scenario="Sucesso" {"success":true,"data":{"user":{},"tenant":{},"memberships":[],"store":{},"channel":{}}}
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user       = $request->user();
        $tenantId   = TenantContext::getId() ?? $user->tenant_id;

        $tenant = $tenantId ? Tenant::where('uuid', $tenantId)->first() : null;

        $memberships = $user->memberships()
            ->with('role.permissions')
            ->where('is_active', true)
            ->get();

        $store   = $tenant ? Store::forTenant($tenant->uuid)->where('type', 'headquarters')->first() : null;
        $channel = $tenant ? Channel::forTenant($tenant->uuid)->where('type', 'pdv')->first() : null;

        return $this->success([
            'user'        => new UserResource($user),
            'tenant'      => $tenant ? new TenantResource($tenant) : null,
            'memberships' => TenantUserResource::collection($memberships),
            'store'       => $store ? new StoreResource($store) : null,
            'channel'     => $channel ? new ChannelResource($channel) : null,
        ]);
    }

    /**
     * Verificar e-mail
     *
     * Confirma o endereço de e-mail via link assinado enviado após o cadastro.
     *
     * @unauthenticated
     *
     * @urlParam id string required UUID do usuário.
     * @urlParam hash string required Hash SHA1 do e-mail.
     *
     * @response 200 scenario="Sucesso" {"success":true,"message":"E-mail verificado com sucesso."}
     */
    public function verifyEmail(Request $request, string $id, string $hash): JsonResponse
    {
        if (! URL::hasValidSignature($request)) {
            abort(403, 'Link de verificação inválido ou expirado.');
        }

        $user = User::where('uuid', $id)->firstOrFail();

        if (! hash_equals(sha1($user->email), $hash)) {
            abort(403, 'Link de verificação inválido.');
        }

        if ($user->hasVerifiedEmail()) {
            return $this->success(message: 'E-mail já verificado.');
        }

        $user->markEmailAsVerified();

        return $this->success(message: 'E-mail verificado com sucesso.');
    }

    /**
     * Reenviar verificação de e-mail
     *
     * Envia novamente o e-mail de verificação para o usuário autenticado.
     *
     * @authenticated
     *
     * @response 200 scenario="Sucesso" {"success":true,"message":"E-mail de verificação reenviado."}
     */
    public function resendVerification(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->success(message: 'E-mail já verificado.');
        }

        $user->sendEmailVerificationNotification();

        return $this->success(message: 'E-mail de verificação reenviado.');
    }

    /**
     * Recuperar senha
     *
     * Envia e-mail com link para redefinição de senha.
     *
     * @unauthenticated
     *
     * @bodyParam email string required E-mail cadastrado. Example: admin@loja.com
     *
     * @response 200 scenario="Sucesso" {"success":true,"message":"E-mail de recuperação enviado."}
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        $user = User::where('email', $request->string('email')->lower()->trim()->toString())
            ->where('is_active', true)
            ->first();

        if ($user) {
            PasswordResetRequested::dispatch($user);
        }

        return $this->success(message: 'E-mail de recuperação enviado.');
    }

    /**
     * Redefinir senha
     *
     * Redefine a senha usando o token enviado por e-mail.
     *
     * @unauthenticated
     *
     * @bodyParam token string required Token recebido por e-mail.
     * @bodyParam email string required E-mail cadastrado. Example: admin@loja.com
     * @bodyParam password string required Nova senha (mínimo 8 caracteres). Example: nova1234
     * @bodyParam password_confirmation string required Confirmação da nova senha. Example: nova1234
     *
     * @response 200 scenario="Sucesso" {"success":true,"message":"Senha redefinida com sucesso."}
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill(['password' => Hash::make($password)])->save();
                $user->tokens()->delete();

                PasswordResetCompleted::dispatch($user);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return $this->success(message: 'Senha redefinida com sucesso.');
    }
}
