<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('import_logs')) {
            return;
        }

        Schema::create('import_logs', function (Blueprint $table): void {
            $table->uuid('uuid')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->foreign('tenant_id')->references('uuid')->on('tenants')->cascadeOnDelete();

            $table->uuid('imported_by')->nullable();
            $table->foreign('imported_by')->references('uuid')->on('users')->nullOnDelete();

            $table->string('import_type', 20); // customers|suppliers
            $table->string('file_name', 255);
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->string('status', 20)->default('processing'); // processing|completed|failed
            $table->jsonb('errors')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'import_type']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
