<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Controllers;

use App\Core\Audit\DTOs\AuditLogDTO;
use App\Core\Audit\Enums\AuditActionEnum;
use App\Core\Audit\Enums\AuditEntityTypeEnum;
use App\Core\Audit\Services\AuditLogger;
use App\Core\Auth\Events\RoleCreated;
use App\Core\Auth\Events\RoleDeleted;
use App\Core\Auth\Events\RoleUpdated;
use App\Core\Auth\Http\Resources\PermissionResource;
use App\Core\Auth\Http\Resources\RoleResource;
use App\Core\Auth\Models\Permission;
use App\Core\Auth\Models\Role;
use App\Core\Auth\Services\PermissionCache;
use App\Core\Tenancy\Services\TenantContext;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class RoleController extends Controller
{
    use AuthorizesRequests;
    use HasApiResponse;
    public function __construct(private readonly AuditLogger $audit) {}

    /** Lista roles disponíveis para o tenant (sistema + custom do tenant). */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        $tenantId = TenantContext::getIdOrFail();

        return $this->success(
            data: RoleResource::collection(
                Role::forTenant($tenantId)->with('permissions')->get(),
            ),
        );
    }

    public function show(Role $role): JsonResponse
    {
        $this->authorize('view', $role);

        return $this->success(data: new RoleResource($role->load('permissions')));
    }

    /** Lista todas as permissões disponíveis no sistema. */
    public function permissions(): JsonResponse
    {
        return $this->success(
            data: PermissionResource::collection(
                Permission::orderBy('module')->orderBy('name')->get(),
            ),
        );
    }

    /**
     * Cria uma role custom para o tenant.
     * Roles de sistema (is_system = true) não podem ser criadas via API.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Role::class);

        $tenantId = TenantContext::getIdOrFail();

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $slug = Str::slug($validated['name']);

        // Ensure slug uniqueness within tenant
        $exists = Role::where('tenant_id', $tenantId)
            ->where('slug', $slug)
            ->withTrashed()
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => ['Já existe uma role com este nome nesta empresa.'],
            ]);
        }

        $role = Role::create([
            'tenant_id'   => $tenantId,
            'name'        => $validated['name'],
            'slug'        => $slug,
            'description' => $validated['description'] ?? null,
            'is_system'   => false,
            'is_active'   => true,
        ]);

        RoleCreated::dispatch($role, $request->user()?->uuid);

        $this->audit->record(new AuditLogDTO(
            entityType: AuditEntityTypeEnum::Role,
            entityUuid: $role->uuid,
            action:     AuditActionEnum::RoleCreated,
            tenantId:   $tenantId,
            userId:     $request->user()?->uuid,
            newValues:  ['name' => $role->name, 'slug' => $role->slug],
            ip:         $request->ip(),
            userAgent:  $request->userAgent(),
        ));

        return response()->json([
            'success' => true,
            'data'    => new RoleResource($role->load('permissions')),
            'message' => 'Role criada com sucesso.',
        ], 201);
    }

    /**
     * Atualiza name/description de uma role custom.
     * Roles de sistema não podem ser modificadas.
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        $this->authorize('update', $role);

        if ($role->isSystemRole()) {
            return response()->json([
                'success' => false,
                'message' => 'Roles de sistema não podem ser modificadas.',
                'errors'  => [],
            ], 403);
        }

        $tenantId = TenantContext::getIdOrFail();

        $validated = $request->validate([
            'name'        => ['sometimes', 'required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $oldValues = ['name' => $role->name, 'description' => $role->description];

        if (isset($validated['name'])) {
            $newSlug = Str::slug($validated['name']);

            $exists = Role::where('tenant_id', $tenantId)
                ->where('slug', $newSlug)
                ->where('uuid', '!=', $role->uuid)
                ->withTrashed()
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'name' => ['Já existe uma role com este nome nesta empresa.'],
                ]);
            }

            $role->slug = $newSlug;
        }

        $role->fill($validated);
        $role->save();

        RoleUpdated::dispatch($role, $request->user()?->uuid);

        $this->audit->record(new AuditLogDTO(
            entityType: AuditEntityTypeEnum::Role,
            entityUuid: $role->uuid,
            action:     AuditActionEnum::RoleUpdated,
            tenantId:   $tenantId,
            userId:     $request->user()?->uuid,
            oldValues:  $oldValues,
            newValues:  ['name' => $role->name, 'description' => $role->description],
            ip:         $request->ip(),
            userAgent:  $request->userAgent(),
        ));

        return response()->json([
            'success' => true,
            'data'    => new RoleResource($role->fresh()->load('permissions')),
            'message' => 'Role atualizada com sucesso.',
        ]);
    }

    /**
     * Exclui (soft delete) uma role custom.
     * Impede exclusão se: for role de sistema ou tiver usuários ativos.
     */
    public function destroy(Request $request, Role $role): JsonResponse
    {
        $this->authorize('delete', $role);

        if ($role->isSystemRole()) {
            return response()->json([
                'success' => false,
                'message' => 'Roles de sistema não podem ser excluídas.',
                'errors'  => [],
            ], 403);
        }

        $activeUsers = $role->tenantUsers()->where('is_active', true)->count();

        if ($activeUsers > 0) {
            return response()->json([
                'success' => false,
                'message' => "Esta role possui {$activeUsers} usuário(s) ativo(s) e não pode ser excluída.",
                'errors'  => [],
            ], 422);
        }

        $tenantId = TenantContext::getIdOrFail();

        RoleDeleted::dispatch($role, $request->user()?->uuid);

        $this->audit->record(new AuditLogDTO(
            entityType: AuditEntityTypeEnum::Role,
            entityUuid: $role->uuid,
            action:     AuditActionEnum::RoleDeleted,
            tenantId:   $tenantId,
            userId:     $request->user()?->uuid,
            oldValues:  ['name' => $role->name, 'slug' => $role->slug],
            ip:         $request->ip(),
            userAgent:  $request->userAgent(),
        ));

        $role->delete();

        return response()->json([
            'success' => true,
            'data'    => null,
            'message' => 'Role excluída com sucesso.',
        ]);
    }

    /**
     * Substitui as permissões de um role custom (sync completo).
     * Apenas roles não-sistema podem ter suas permissões alteradas.
     */
    public function syncPermissions(Request $request, Role $role, PermissionCache $cache): JsonResponse
    {
        $this->authorize('syncPermissions', $role);

        $validated = $request->validate([
            'permission_ids'   => ['required', 'array'],
            'permission_ids.*' => ['uuid', 'exists:permissions,uuid'],
        ]);

        $role->permissions()->sync($validated['permission_ids']);

        $tenantId = TenantContext::getIdOrFail();
        $cache->invalidateTenantRole($tenantId, $role->uuid);

        return $this->success(data: new RoleResource($role->fresh()->load('permissions')));
    }
}
