<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_addresses', function (Blueprint $table): void {
            if (! Schema::hasColumn('supplier_addresses', 'address_type')) {
                $table->string('address_type', 20)->default('headquarters')->after('is_default');
            }
        });
    }

    public function down(): void
    {
        Schema::table('supplier_addresses', function (Blueprint $table): void {
            $table->dropColumn('address_type');
        });
    }
};
