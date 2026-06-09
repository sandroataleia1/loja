<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove o campo legacy `role` da tabela users.
 *
 * O sistema usa RBAC completo via tenant_users → roles → role_permissions.
 * A coluna role era um resquício que conflitava com o RBAC e permitia bypass
 * de autorização via RoleEnum::Admin/Manager/Operator diretamente no model.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('operator')->after('password');
        });
    }
};
