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
            // [1.1] Cônjuge — complemento (spouse_cpf separado do document genérico)
            if (! Schema::hasColumn('customers', 'spouse_rg')) {
                $table->string('spouse_rg', 20)->nullable()->after('spouse_document');
            }
            if (! Schema::hasColumn('customers', 'spouse_cpf')) {
                $table->string('spouse_cpf', 14)->nullable()->after('spouse_rg');
            }
            if (! Schema::hasColumn('customers', 'spouse_monthly_income')) {
                $table->decimal('spouse_monthly_income', 12, 2)->nullable()->after('spouse_income');
            }

            // [1.2] Análise de crédito
            if (! Schema::hasColumn('customers', 'monthly_income')) {
                $table->decimal('monthly_income', 12, 2)->nullable()->after('credit_limit');
            }
            if (! Schema::hasColumn('customers', 'profession')) {
                $table->string('profession', 100)->nullable()->after('monthly_income');
            }
            if (! Schema::hasColumn('customers', 'employer')) {
                $table->string('employer', 150)->nullable()->after('profession');
            }
            if (! Schema::hasColumn('customers', 'employer_phone')) {
                $table->string('employer_phone', 20)->nullable()->after('employer');
            }
            if (! Schema::hasColumn('customers', 'work_start_date')) {
                $table->date('work_start_date')->nullable()->after('employer_phone');
            }
            if (! Schema::hasColumn('customers', 'credit_score')) {
                $table->unsignedTinyInteger('credit_score')->nullable()->after('work_start_date'); // 0–10
            }
            if (! Schema::hasColumn('customers', 'credit_analysis_date')) {
                $table->date('credit_analysis_date')->nullable()->after('credit_score');
            }
            if (! Schema::hasColumn('customers', 'credit_analyzed_by')) {
                $table->foreignUuid('credit_analyzed_by')->nullable()->after('credit_analysis_date')
                    ->constrained('users', 'uuid')->nullOnDelete();
            }
            if (! Schema::hasColumn('customers', 'credit_notes')) {
                $table->text('credit_notes')->nullable()->after('credit_analyzed_by');
            }
            if (! Schema::hasColumn('customers', 'spc_consulted_at')) {
                $table->timestamp('spc_consulted_at')->nullable()->after('credit_notes');
            }
            if (! Schema::hasColumn('customers', 'spc_status')) {
                $table->string('spc_status', 20)->nullable()->after('spc_consulted_at'); // clean|restricted|unknown
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $cols = [
                'spouse_rg', 'spouse_cpf', 'spouse_monthly_income',
                'monthly_income', 'profession', 'employer', 'employer_phone',
                'work_start_date', 'credit_score', 'credit_analysis_date',
                'credit_analyzed_by', 'credit_notes', 'spc_consulted_at', 'spc_status',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('customers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
