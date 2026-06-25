<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carriers', function (Blueprint $table): void {
            if (! Schema::hasColumn('carriers', 'delivery_mode')) {
                $table->string('delivery_mode', 20)->default('own_fleet')->after('is_active');
                $table->index(['tenant_id', 'delivery_mode']);
            }
            if (! Schema::hasColumn('carriers', 'rntrc')) {
                $table->string('rntrc', 20)->nullable()->after('delivery_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('carriers', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'delivery_mode']);
            $table->dropColumn(['delivery_mode', 'rntrc']);
        });
    }
};
