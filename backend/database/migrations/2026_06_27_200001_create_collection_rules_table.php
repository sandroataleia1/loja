<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_rules', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->string('name', 100);
            // Dias após o vencimento para ativar a regra
            $table->unsignedSmallInteger('days_after_due');
            // Ação: email, sms, block_sales, flag_customer
            $table->string('action', 30);
            // Template de mensagem para email/sms (usa variáveis {{name}}, {{amount}}, {{due_date}})
            $table->text('message_template')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active', 'days_after_due']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_rules');
    }
};
