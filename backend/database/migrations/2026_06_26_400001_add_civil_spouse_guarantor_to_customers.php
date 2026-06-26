<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'civil_status'))
                $table->string('civil_status', 30)->nullable()->after('birth_date');
            if (!Schema::hasColumn('customers', 'spouse_name'))
                $table->string('spouse_name', 200)->nullable()->after('civil_status');
            if (!Schema::hasColumn('customers', 'spouse_document'))
                $table->string('spouse_document', 20)->nullable()->after('spouse_name');
            if (!Schema::hasColumn('customers', 'spouse_phone'))
                $table->string('spouse_phone', 20)->nullable()->after('spouse_document');
            if (!Schema::hasColumn('customers', 'spouse_employer'))
                $table->string('spouse_employer', 200)->nullable()->after('spouse_phone');
            if (!Schema::hasColumn('customers', 'spouse_income'))
                $table->decimal('spouse_income', 12, 2)->nullable()->after('spouse_employer');
            if (!Schema::hasColumn('customers', 'guarantor_name'))
                $table->string('guarantor_name', 200)->nullable()->after('spouse_income');
            if (!Schema::hasColumn('customers', 'guarantor_document'))
                $table->string('guarantor_document', 20)->nullable()->after('guarantor_name');
            if (!Schema::hasColumn('customers', 'guarantor_phone'))
                $table->string('guarantor_phone', 20)->nullable()->after('guarantor_document');
            if (!Schema::hasColumn('customers', 'guarantor_address'))
                $table->text('guarantor_address')->nullable()->after('guarantor_phone');
            if (!Schema::hasColumn('customers', 'guarantor_income'))
                $table->decimal('guarantor_income', 12, 2)->nullable()->after('guarantor_address');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'civil_status', 'spouse_name', 'spouse_document', 'spouse_phone',
                'spouse_employer', 'spouse_income', 'guarantor_name', 'guarantor_document',
                'guarantor_phone', 'guarantor_address', 'guarantor_income',
            ]);
        });
    }
};
