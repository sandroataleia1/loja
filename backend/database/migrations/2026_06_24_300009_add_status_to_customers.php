<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            if (! Schema::hasColumn('customers', 'status')) {
                $table->string('status', 20)->default('active')->after('is_active');
            }
            if (! Schema::hasColumn('customers', 'blocked_reason')) {
                $table->text('blocked_reason')->nullable()->after('status');
            }
            if (! Schema::hasColumn('customers', 'blocked_at')) {
                $table->timestamp('blocked_at')->nullable()->after('blocked_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn(['status', 'blocked_reason', 'blocked_at']);
        });
    }
};
