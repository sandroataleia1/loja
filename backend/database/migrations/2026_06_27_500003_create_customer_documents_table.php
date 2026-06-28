<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_documents')) {
            return;
        }

        Schema::create('customer_documents', function (Blueprint $table): void {
            $table->uuid()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants', 'uuid')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained('customers', 'uuid')->cascadeOnDelete();
            // rg|cpf|cnpj|comprovante_residencia|comprovante_renda|contrato_social|outro
            $table->string('document_type', 30);
            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->unsignedInteger('file_size');    // bytes
            $table->string('mime_type', 100);
            $table->string('notes', 500)->nullable();
            $table->foreignUuid('uploaded_by')->constrained('users', 'uuid');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'customer_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_documents');
    }
};
