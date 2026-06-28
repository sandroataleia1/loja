<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Definições de campos customizados ─────────────────────────────────
        Schema::create('custom_field_definitions', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            // Entidade suportada: customer, supplier, product, order, quote
            $table->string('entity_type', 50);
            $table->string('label', 100);      // nome exibido ao usuário
            $table->string('field_key', 60);   // chave programática (snake_case)
            // Tipo do campo
            $table->string('field_type', 20)->default('text'); // text|number|date|boolean|select|multiselect|textarea
            // Opções para select/multiselect (JSON array de strings)
            $table->jsonb('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_searchable')->default(false);    // indexar para busca
            $table->boolean('is_visible_in_list')->default(false); // exibir em listagens
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('placeholder', 200)->nullable();
            $table->string('help_text', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'entity_type', 'field_key'], 'uq_custom_field_def');
            $table->index(['tenant_id', 'entity_type', 'is_active']);
        });

        // ── Valores dos campos customizados ───────────────────────────────────
        Schema::create('custom_field_values', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('field_definition_id')
                ->constrained('custom_field_definitions', 'uuid')
                ->cascadeOnDelete();
            // Polimórfico: referencia qualquer entidade por tipo + uuid
            $table->string('entity_type', 50);
            $table->uuid('entity_id');
            // Armazena em colunas tipadas para facilitar queries e índices
            $table->text('value_text')->nullable();
            $table->decimal('value_number', 18, 4)->nullable();
            $table->date('value_date')->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->jsonb('value_json')->nullable(); // para multiselect
            $table->timestamps();

            $table->unique(['field_definition_id', 'entity_id'], 'uq_custom_field_value');
            $table->index(['tenant_id', 'entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_values');
        Schema::dropIfExists('custom_field_definitions');
    }
};
