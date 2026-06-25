<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carrier_contacts', function (Blueprint $table): void {
            if (! Schema::hasColumn('carrier_contacts', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('carrier_contacts', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
