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
            // Filiação
            if (! Schema::hasColumn('customers', 'father_name')) {
                $table->string('father_name', 150)->nullable();
            }
            if (! Schema::hasColumn('customers', 'mother_name')) {
                $table->string('mother_name', 150)->nullable();
            }

            // Dados pessoais detalhados
            if (! Schema::hasColumn('customers', 'gender')) {
                $table->string('gender', 10)->nullable();
            }
            if (! Schema::hasColumn('customers', 'nationality')) {
                $table->string('nationality', 80)->nullable()->default('Brasileira');
            }
            if (! Schema::hasColumn('customers', 'birth_city')) {
                $table->string('birth_city', 100)->nullable();
            }
            if (! Schema::hasColumn('customers', 'birth_state')) {
                $table->string('birth_state', 2)->nullable();
            }
            if (! Schema::hasColumn('customers', 'education_level')) {
                $table->string('education_level', 20)->nullable();
            }
            if (! Schema::hasColumn('customers', 'years_at_address')) {
                $table->unsignedTinyInteger('years_at_address')->nullable();
            }
            if (! Schema::hasColumn('customers', 'housing_type')) {
                $table->string('housing_type', 20)->nullable();
            }
            if (! Schema::hasColumn('customers', 'rent_cents')) {
                $table->unsignedInteger('rent_cents')->nullable();
            }

            // Dados profissionais complementares
            if (! Schema::hasColumn('customers', 'professional_card')) {
                $table->string('professional_card', 30)->nullable();
            }
            if (! Schema::hasColumn('customers', 'employer_address')) {
                $table->string('employer_address', 300)->nullable();
            }
            if (! Schema::hasColumn('customers', 'other_income')) {
                $table->decimal('other_income', 12, 2)->nullable();
            }
            if (! Schema::hasColumn('customers', 'other_income_source')) {
                $table->string('other_income_source', 150)->nullable();
            }

            // Estado civil normalizado (marital_status enum vs civil_status legacy)
            if (! Schema::hasColumn('customers', 'marital_status')) {
                $table->string('marital_status', 20)->nullable();
            }

            // Limite de crédito em centavos (substituirá credit_limit decimal)
            if (! Schema::hasColumn('customers', 'credit_limit_cents')) {
                $table->unsignedBigInteger('credit_limit_cents')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn([
                'father_name', 'mother_name', 'gender', 'nationality',
                'birth_city', 'birth_state', 'education_level',
                'years_at_address', 'housing_type', 'rent_cents',
                'professional_card', 'employer_address',
                'other_income', 'other_income_source',
                'marital_status', 'credit_limit_cents',
            ]);
        });
    }
};
