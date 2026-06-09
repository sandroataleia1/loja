<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |----------------------------------------------------------------------
        | tenant_fiscal_settings — configuração fiscal por tenant
        |----------------------------------------------------------------------
        */
        Schema::create('tenant_fiscal_settings', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->uuid('tenant_id')->unique(); // um por tenant
            $table->string('company_name', 200);
            $table->string('cnpj', 18);
            $table->string('ie', 20)->nullable();
            $table->unsignedTinyInteger('crt'); // 1=Simples, 2=Presumido, 3=Real, 4=MEI
            $table->string('csc', 36)->nullable();      // Código de Segurança do Contribuinte
            $table->string('csc_id', 10)->nullable();    // ID do CSC
            $table->string('certificate_path', 500)->nullable();
            $table->text('certificate_password_encrypted')->nullable();
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('uuid')->on('tenants')->onDelete('cascade');
        });

        /*
        |----------------------------------------------------------------------
        | tax_profiles — perfis fiscais reutilizáveis (NCM + regime)
        |----------------------------------------------------------------------
        */
        Schema::create('tax_profiles', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->uuid('tenant_id');
            $table->string('name', 100);
            $table->string('regime', 30); // TaxRegimeEnum
            $table->jsonb('metadata')->default('{}');
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('uuid')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'name']);
        });

        /*
        |----------------------------------------------------------------------
        | fiscal_documents — documentos fiscais emitidos
        |
        | Não usa SoftDeletes: documentos fiscais são registros legais imutáveis.
        | Cancelamentos criam novo estado via status = CANCELLED.
        |----------------------------------------------------------------------
        */
        Schema::create('fiscal_documents', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->uuid('tenant_id');
            $table->uuid('store_id');
            $table->uuid('sale_id')->nullable(); // nullable: docs sem venda (NF-e avulsa futura)
            $table->string('type', 10);          // FiscalDocumentTypeEnum
            $table->string('status', 20);        // FiscalDocumentStatusEnum
            $table->string('provider', 30);      // FiscalProviderEnum
            $table->string('access_key', 44)->nullable()->unique(); // chave de acesso SEFAZ
            $table->string('protocol', 30)->nullable();              // protocolo de autorização
            $table->string('xml_path', 500)->nullable();             // caminho do XML persistido
            $table->string('pdf_path', 500)->nullable();             // caminho do DANFE/PDF
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->boolean('contingency_mode')->default(false);
            $table->text('error_message')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps(); // updated_at usado para rastrear última tentativa

            $table->foreign('tenant_id')->references('uuid')->on('tenants')->onDelete('cascade');
            $table->foreign('store_id')->references('uuid')->on('stores')->onDelete('cascade');
            $table->foreign('sale_id')->references('uuid')->on('sales')->onDelete('set null');

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'sale_id']);
            $table->index(['tenant_id', 'type', 'issued_at']);
        });

        /*
        |----------------------------------------------------------------------
        | catalog_variants — adiciona campos fiscais
        |----------------------------------------------------------------------
        */
        Schema::table('catalog_variants', function (Blueprint $table): void {
            $table->string('ncm', 10)->nullable()->after('metadata');       // NCM — 8 dígitos
            $table->string('cest', 9)->nullable()->after('ncm');            // CEST — 7 dígitos
            $table->string('cfop_default', 5)->nullable()->after('cest');   // CFOP padrão ex: 5102
            $table->unsignedTinyInteger('origin_code')->default(0)->after('cfop_default'); // 0-8
            $table->uuid('tax_profile_id')->nullable()->after('origin_code');

            $table->foreign('tax_profile_id')->references('uuid')->on('tax_profiles')->onDelete('set null');
            $table->index('ncm');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_variants', function (Blueprint $table): void {
            $table->dropForeign(['tax_profile_id']);
            $table->dropIndex(['ncm']);
            $table->dropColumn(['ncm', 'cest', 'cfop_default', 'origin_code', 'tax_profile_id']);
        });

        Schema::dropIfExists('fiscal_documents');
        Schema::dropIfExists('tax_profiles');
        Schema::dropIfExists('tenant_fiscal_settings');
    }
};
