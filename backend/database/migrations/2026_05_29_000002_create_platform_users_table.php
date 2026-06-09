<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Usuários da plataforma (super_admin).
        // Sem tenant_id — acesso cross-tenant para operações administrativas.
        // Isolado da tabela users (TenantUser) por design: nenhuma global scope
        // de tenant deve ser aplicada a PlatformUser.
        Schema::create('platform_users', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->string('name', 120);
            $table->string('email', 254)->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_users');
    }
};
