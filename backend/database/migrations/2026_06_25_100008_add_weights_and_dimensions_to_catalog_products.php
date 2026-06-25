<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->integer('weight_gross_g')->nullable()->after('metadata');
            $table->integer('weight_net_g')->nullable()->after('weight_gross_g');
            // {width_cm, height_cm, depth_cm} — dimensões da embalagem
            $table->jsonb('dimensions')->nullable()->after('weight_net_g');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_products', function (Blueprint $table): void {
            $table->dropColumn(['weight_gross_g', 'weight_net_g', 'dimensions']);
        });
    }
};
