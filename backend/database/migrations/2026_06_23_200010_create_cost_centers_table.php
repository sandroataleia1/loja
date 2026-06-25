<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Perfil de centros de custo — hierárquico (parent_id self-ref).
 *
 * IMPORTANTE: o PK deve ser criado ANTES do FK self-referencial.
 * O `->primary()` do Blueprint gera ALTER TABLE no FINAL da fila de comandos,
 * depois dos FKs — causaria erro no PostgreSQL. Por isso criamos o PK inline
 * via rawColumn e adicionamos o FK num segundo Schema::table() separado.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cost_centers')) {
            // Etapa 1: cria a tabela SEM o FK self-referencial
            Schema::create('cost_centers', function (Blueprint $table): void {
                $table->uuid('uuid')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
                $table->uuid('parent_id')->nullable();
                $table->string('code', 20);
                $table->string('name', 100);
                $table->string('type', 30)->default('ADMINISTRATIVE');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'code'], 'cost_centers_tenant_code_unique');
                $table->index(['tenant_id', 'parent_id'], 'cost_centers_tenant_parent_idx');
            });

            // Etapa 2: agora que o PK existe, adiciona o FK self-referencial
            DB::statement('
                ALTER TABLE cost_centers
                ADD CONSTRAINT cost_centers_parent_id_foreign
                FOREIGN KEY (parent_id)
                REFERENCES cost_centers (uuid)
                ON DELETE SET NULL
            ');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_centers');
    }
};
