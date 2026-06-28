<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_features', function (Blueprint $table): void {
            $table->jsonb('config')->nullable()->after('metadata');
            $table->timestamp('enabled_at')->nullable()->after('config');
            $table->foreignUuid('enabled_by')
                ->nullable()
                ->after('enabled_at')
                ->constrained('users', 'uuid')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tenant_features', function (Blueprint $table): void {
            $table->dropForeign(['enabled_by']);
            $table->dropColumn(['config', 'enabled_at', 'enabled_by']);
        });
    }
};
