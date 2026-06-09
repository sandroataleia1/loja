<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Controllers;

use App\Core\Auth\Actions\AssignRoleAction;
use App\Core\Auth\Actions\GrantStoreAccessAction;
use App\Core\Auth\Actions\RevokeRoleAction;
use App\Core\Auth\Actions\RevokeStoreAccessAction;
use App\Core\Auth\DTOs\AssignRoleDTO;
use Illuminate\Support\Facades\Hash;
use App\Core\Auth\DTOs\GrantStoreAccessDTO;
use App\Core\Auth\Http\Requests\AssignRoleRequest;
use App\Core\Auth\Http\Requests\GrantStoreAccessRequest;
use App\Core\Auth\Http\Resources\TenantUserResource;
use App\Core\Auth\Models\Role;
use App\Core\Auth\Models\TenantUser;
use App\Core\Auth\Models\User;
use App\Core\Tenancy\Services\TenantContext;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

final class TenantUserController extends Controller
{
    use AuthorizesRequests;
    use HasApiResponse;

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', TenantUser::class);
        $tenantId = TenantContext::getIdOrFail();

        return $this->success(
            data: TenantUserResource::collection(
                TenantUser::where('tenant_id', $tenantId)
                    ->with(['user', 'role', 'storeAccesses'])
                    ->paginate(50),
            ),
        );
    }

    public function show(TenantUser $tenantUser): JsonResponse
    {
        $this->authorize('view', $tenantUser);

        return $this->success(
            data: new TenantUserResource($tenantUser->load(['user', 'role.permissions', 'storeAccesses'])),
        );
    }

    /**
     * Cria um usuário novo e já adiciona ao tenant com um role.
     * Útil para o admin criar colaboradores sem que eles precisem se registrar.
     */
    public function createUser(Request $request, AssignRoleAction $action): JsonResponse
    {
        $this->authorize('create', TenantUser::class);

        $tenantId = TenantContext::getIdOrFail();

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role_id'  => ['required', 'uuid', 'exists:roles,uuid'],
        ]);

        // Cria o usuário
        $user = User::create([
            'name'                => $data['name'],
            'email'               => $data['email'],
            'password'            => Hash::make($data['password']),
            'tenant_id'           => $tenantId,
            'is_active'           => true,
            'email_verified_at'   => now(), // admin criou, e-mail já confiável
        ]);

        // Adiciona ao tenant com o role escolhido
        $tenantUser = $action->execute(new AssignRoleDTO(
            tenantId:   $tenantId,
            userId:     $user->uuid,
            roleId:     $data['role_id'],
            assignedBy: auth()->id(),
        ));

        return $this->created(
            data: new TenantUserResource($tenantUser->load(['user', 'role', 'storeAccesses'])),
            message: 'Usuário criado com sucesso.',
        );
    }

    /** Atribui (ou substitui) um role para um usuário no tenant. */
    public function assign(AssignRoleRequest $request, AssignRoleAction $action): JsonResponse
    {
        $this->authorize('create', TenantUser::class);
        $tenantId = TenantContext::getIdOrFail();

        $tenantUser = $action->execute(new AssignRoleDTO(
            tenantId:   $tenantId,
            userId:     $request->validated('user_id'),
            roleId:     $request->validated('role_id'),
            assignedBy: auth()->id(),
        ));

        return $this->created(
            data: new TenantUserResource($tenantUser->load(['user', 'role', 'storeAccesses'])),
            message: 'Perfil atribuído com sucesso.',
        );
    }

    /** Busca usuário pelo e-mail para convidar ao tenant. */
    public function lookupByEmail(Request $request): JsonResponse
    {
        $this->authorize('create', TenantUser::class);

        $email = $request->string('email')->toString();
        $user  = User::where('email', $email)->first();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhum usuário encontrado com este e-mail.',
                'errors'  => [],
            ], 404);
        }

        $tenantId = TenantContext::getIdOrFail();
        $alreadyMember = TenantUser::where('tenant_id', $tenantId)
            ->where('user_id', $user->uuid)
            ->exists();

        if ($alreadyMember) {
            return response()->json([
                'success' => false,
                'message' => 'Este usuário já é membro do tenant.',
                'errors'  => [],
            ], 422);
        }

        return $this->success([
            'uuid'  => $user->uuid,
            'name'  => $user->name,
            'email' => $user->email,
        ]);
    }

    /** Troca o role de um TenantUser existente. */
    public function changeRole(Request $request, TenantUser $tenantUser): JsonResponse
    {
        $this->authorize('update', $tenantUser);

        $request->validate([
            'role_id' => ['required', 'uuid', 'exists:roles,uuid'],
        ]);

        $tenantId = TenantContext::getIdOrFail();
        $role = Role::forTenant($tenantId)->where('uuid', $request->string('role_id'))->firstOrFail();

        $tenantUser->update(['role_id' => $role->uuid]);

        return $this->success(
            data: new TenantUserResource($tenantUser->fresh()->load(['user', 'role', 'storeAccesses'])),
            message: 'Role atualizado com sucesso.',
        );
    }

    /** Revoga o acesso do usuário ao tenant. */
    public function revoke(TenantUser $tenantUser, RevokeRoleAction $action): JsonResponse
    {
        $this->authorize('delete', $tenantUser);
        $tenantId = TenantContext::getIdOrFail();

        $action->execute($tenantId, $tenantUser->user_id, auth()->id());

        return $this->success(message: 'Acesso revogado com sucesso.');
    }

    /** Adiciona uma loja à allowlist do TenantUser. */
    public function grantStoreAccess(
        GrantStoreAccessRequest $request,
        TenantUser $tenantUser,
        GrantStoreAccessAction $action,
    ): JsonResponse {
        $this->authorize('update', $tenantUser);
        $tenantId = TenantContext::getIdOrFail();

        $action->execute(
            new GrantStoreAccessDTO(
                tenantUserId: $tenantUser->uuid,
                storeId:      $request->validated('store_id'),
            ),
            $tenantId,
        );

        return $this->created(message: 'Acesso à loja concedido.');
    }

    /** Remove uma loja da allowlist. Se todas forem removidas, acesso volta a ser irrestrito. */
    public function revokeStoreAccess(
        TenantUser $tenantUser,
        string $storeId,
        RevokeStoreAccessAction $action,
    ): JsonResponse {
        $this->authorize('update', $tenantUser);
        $tenantId = TenantContext::getIdOrFail();

        $action->execute($tenantUser->uuid, $storeId, $tenantId);

        return $this->success(message: 'Acesso à loja revogado.');
    }
}
