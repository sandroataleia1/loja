<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('import_logs')) {
            return;
        }

        if (Schema::hasColumn('import_logs', 'deleted_at')) {
            return;
        }

        Schema::table('import_logs', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('import_logs', 'deleted_at')) {
            Schema::table('import_logs', function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }
    }
};
