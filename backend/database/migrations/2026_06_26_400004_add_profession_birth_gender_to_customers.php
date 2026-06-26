<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'spouse_profession')) {
                $table->string('spouse_profession', 200)->nullable()->after('spouse_employer');
            }
            if (! Schema::hasColumn('customers', 'spouse_birth_date')) {
                $table->date('spouse_birth_date')->nullable()->after('spouse_profession');
            }
            if (! Schema::hasColumn('customers', 'spouse_gender')) {
                $table->char('spouse_gender', 1)->nullable()->after('spouse_birth_date'); // M, F, O
            }
            if (! Schema::hasColumn('customers', 'guarantor_profession')) {
                $table->string('guarantor_profession', 200)->nullable()->after('guarantor_income');
            }
            if (! Schema::hasColumn('customers', 'guarantor_birth_date')) {
                $table->date('guarantor_birth_date')->nullable()->after('guarantor_profession');
            }
            if (! Schema::hasColumn('customers', 'guarantor_gender')) {
                $table->char('guarantor_gender', 1)->nullable()->after('guarantor_birth_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'spouse_profession',
                'spouse_birth_date',
                'spouse_gender',
                'guarantor_profession',
                'guarantor_birth_date',
                'guarantor_gender',
            ]);
        });
    }
};
