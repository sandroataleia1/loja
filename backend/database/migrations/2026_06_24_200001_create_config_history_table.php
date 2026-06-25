<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('config_history', function (Blueprint $table): void {
            $table->uuid('uuid')->primary()->default(\Illuminate\Support\Facades\DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->foreign('tenant_id')->references('uuid')->on('tenants')->cascadeOnDelete();

            $table->string('section', 50);
            $table->string('key', 100);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();

            $table->uuid('changed_by')->nullable();
            $table->foreign('changed_by')->references('uuid')->on('users')->nullOnDelete();

            $table->timestamp('changed_at')->useCurrent();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();

            // Sem updated_at — registro imutável
            $table->index(['tenant_id', 'section'],    'config_history_tenant_section_idx');
            $table->index(['tenant_id', 'changed_at'], 'config_history_tenant_date_idx');
            $table->index(['tenant_id', 'key'],        'config_history_tenant_key_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('config_history');
    }
};
