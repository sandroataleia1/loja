<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_conditions', function (Blueprint $table): void {
            $table->string('type', 30)->default('a_vista')->after('name')
                  ->comment('a_vista | parcelado | entrada_parcelas | variavel');

            // Desconto
            $table->string('discount_type', 10)->default('none')->after('type')
                  ->comment('none | percent | fixed');
            $table->decimal('discount_value', 10, 4)->default(0)->after('discount_type');

            // Juros de parcelamento (embutido na geração de parcelas)
            $table->string('interest_type', 30)->default('none')->after('discount_value')
                  ->comment('none | percent_month | percent_total | fixed_per_installment | fixed_total');
            $table->decimal('interest_value', 10, 4)->default(0)->after('interest_type');

            // Multa por atraso
            $table->decimal('fine_percent', 10, 4)->default(0)->after('interest_value');
            $table->unsignedSmallInteger('fine_after_days')->default(0)->after('fine_percent')
                  ->comment('Dias de carência antes de aplicar multa');

            // Carência geral
            $table->unsignedSmallInteger('grace_days')->default(0)->after('fine_after_days');

            // Parcelamento
            $table->unsignedSmallInteger('installment_count')->default(1)->after('grace_days')
                  ->comment('Número de parcelas. 0 = variável');
            $table->unsignedSmallInteger('first_due_days')->default(0)->after('installment_count')
                  ->comment('Dias até o primeiro vencimento a partir da data de venda');
            $table->unsignedSmallInteger('interval_days')->default(30)->after('first_due_days')
                  ->comment('Intervalo em dias entre parcelas');
            $table->boolean('has_entry')->default(false)->after('interval_days')
                  ->comment('Possui entrada no ato da venda');
            $table->decimal('entry_percent', 10, 4)->default(0)->after('has_entry')
                  ->comment('% do total cobrado como entrada');
            $table->boolean('is_variable')->default(false)->after('entry_percent')
                  ->comment('Número de parcelas definido no momento da venda');
        });
    }

    public function down(): void
    {
        Schema::table('payment_conditions', function (Blueprint $table): void {
            $table->dropColumn([
                'type',
                'discount_type',
                'discount_value',
                'interest_type',
                'interest_value',
                'fine_percent',
                'fine_after_days',
                'grace_days',
                'installment_count',
                'first_due_days',
                'interval_days',
                'has_entry',
                'entry_percent',
                'is_variable',
            ]);
        });
    }
};
