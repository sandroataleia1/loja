<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feature flags por tenant.
 *
 * Controla quais módulos estão habilitados para cada tenant:
 * fiscal, financeiro, multi-loja, offline, social commerce, IA, omnichannel.
 *
 * Verificação: TenantFeature::isEnabled($tenantId, TenantFeatureEnum::FiscalEnabled)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_features', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->string('feature', 100);
            $table->boolean('is_enabled')->default(false);
            $table->json('metadata')->nullable(); // configurações extras da feature
            $table->timestamps();

            $table->unique(['tenant_id', 'feature'], 'tenant_features_unique');
            $table->index(['tenant_id', 'is_enabled'], 'tenant_features_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_features');
    }
};
