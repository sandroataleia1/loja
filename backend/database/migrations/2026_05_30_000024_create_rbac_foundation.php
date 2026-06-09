<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fundação RBAC: permissions, roles, role_permissions, tenant_users, tenant_user_stores, platform_roles.
 *
 * Decisões de design:
 * - permissions são globais (sem tenant_id) — slugs são contratos de código
 * - roles têm tenant_id nullable: NULL = role de sistema, UUID = role custom do tenant
 * - tenant_users é a tabela de membership: um user pode ter roles distintas por tenant
 * - tenant_user_stores: ausência de linhas = acesso irrestrito (allowlist)
 * - platform_roles são para super-admins SaaS (não pertencem a tenant)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Permissions ───────────────────────────────────────────────────────
        Schema::create('permissions', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->string('module', 50);
            $table->boolean('is_system')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->index('module', 'permissions_module_idx');
        });

        // ── Roles (tenant_id nullable = role de sistema) ──────────────────────
        Schema::create('roles', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->foreignUuid('tenant_id')->nullable()->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Mesmo slug pode existir em tenants diferentes (mas não duplicado no mesmo tenant)
            $table->unique(['tenant_id', 'slug'], 'roles_tenant_slug_unique');
            $table->index(['tenant_id', 'is_active'], 'roles_tenant_active_idx');
        });

        // ── Role → Permission (many-to-many) ──────────────────────────────────
        Schema::create('role_permissions', function (Blueprint $table): void {
            $table->foreignUuid('role_id')->constrained('roles', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('permission_id')->constrained('permissions', 'uuid')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        // ── Tenant membership ─────────────────────────────────────────────────
        Schema::create('tenant_users', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('role_id')->constrained('roles', 'uuid')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            // Um usuário pertence a um tenant uma única vez (role é mutável)
            $table->unique(['tenant_id', 'user_id'], 'tenant_users_tenant_user_unique');
            $table->index(['tenant_id', 'is_active'], 'tenant_users_active_idx');
            $table->index(['user_id', 'is_active'],   'tenant_users_user_active_idx');
        });

        // ── Store access (allowlist: ausência = irrestrito) ───────────────────
        Schema::create('tenant_user_stores', function (Blueprint $table): void {
            $table->foreignUuid('tenant_user_id')->constrained('tenant_users', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores', 'uuid')->cascadeOnDelete();
            $table->primary(['tenant_user_id', 'store_id']);

            $table->index('store_id', 'tenant_user_stores_store_idx');
        });

        // ── Platform roles (SaaS super-admin, suporte) ────────────────────────
        Schema::create('platform_roles', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_user_stores');
        Schema::dropIfExists('tenant_users');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('platform_roles');
    }
};
