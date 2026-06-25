<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_variant_attributes', function (Blueprint $table): void {
            $table->string('value_text', 200)->nullable()->after('sort_order');
            $table->decimal('value_number', 12, 4)->nullable()->after('value_text');
            $table->foreignUuid('value_unit_id')
                ->nullable()
                ->after('value_number')
                ->constrained('units', 'uuid')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('catalog_variant_attributes', function (Blueprint $table): void {
            $table->dropForeign(['value_unit_id']);
            $table->dropColumn(['value_text', 'value_number', 'value_unit_id']);
        });
    }
};
