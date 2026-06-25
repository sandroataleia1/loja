<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table): void {
            if (! Schema::hasColumn('suppliers', 'status')) {
                $table->string('status', 20)->default('active')->after('is_active');
            }
            if (! Schema::hasColumn('suppliers', 'suspension_reason')) {
                $table->text('suspension_reason')->nullable()->after('status');
            }
            if (! Schema::hasColumn('suppliers', 'suspended_at')) {
                $table->timestamp('suspended_at')->nullable()->after('suspension_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table): void {
            $table->dropColumn(['status', 'suspension_reason', 'suspended_at']);
        });
    }
};
