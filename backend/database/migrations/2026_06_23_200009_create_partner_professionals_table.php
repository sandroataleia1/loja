<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_professionals', function (Blueprint $table): void {
            $table->uuid('uuid')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->string('code', 20)->nullable();
            $table->string('type', 30)->default('OTHER'); // MASON|FOREMAN|ARCHITECT|ENGINEER|DESIGNER|OTHER
            $table->string('name', 200);
            $table->string('document', 20)->nullable(); // CPF
            $table->string('email', 254)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'type'], 'partners_tenant_type_idx');
            $table->index(['tenant_id', 'is_active'], 'partners_tenant_active_idx');
        });

        DB::statement(
            'CREATE UNIQUE INDEX partners_tenant_code_unique '
            . 'ON partner_professionals (tenant_id, code) '
            . 'WHERE deleted_at IS NULL AND code IS NOT NULL'
        );

        Schema::create('partner_contacts', function (Blueprint $table): void {
            $table->uuid('uuid')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('partner_id')->constrained('partner_professionals', 'uuid')->cascadeOnDelete();
            $table->string('type', 20); // PHONE|WHATSAPP|EMAIL|OTHER
            $table->string('value', 200);
            $table->string('label', 100)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index('partner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_contacts');
        Schema::dropIfExists('partner_professionals');
    }
};
