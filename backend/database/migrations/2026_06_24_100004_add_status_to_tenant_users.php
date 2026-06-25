<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona coluna `status` (active|inactive|suspended) em tenant_users.
 *
 * Retrocompatibilidade: is_active permanece sincronizado com status:
 *   active    → is_active = true
 *   inactive  → is_active = false
 *   suspended → is_active = false
 *
 * A sincronização é feita no Model TenantUser (não trigger de banco)
 * para manter compatibilidade cross-database.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_users', function (Blueprint $table): void {
            $table->string('status', 20)->default('active')->after('is_active');
            $table->index(['tenant_id', 'status'], 'tenant_users_status_idx');
        });

        // Preenche status baseado no is_active existente
        DB::table('tenant_users')->where('is_active', true)->update(['status' => 'active']);
        DB::table('tenant_users')->where('is_active', false)->update(['status' => 'inactive']);
    }

    public function down(): void
    {
        Schema::table('tenant_users', function (Blueprint $table): void {
            $table->dropIndex('tenant_users_status_idx');
            $table->dropColumn('status');
        });
    }
};
