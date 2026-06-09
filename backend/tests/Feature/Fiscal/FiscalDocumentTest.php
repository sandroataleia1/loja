<?php

declare(strict_types=1);

use App\Modules\Fiscal\Enums\FiscalDocumentStatusEnum;
use App\Modules\Fiscal\Enums\FiscalDocumentTypeEnum;
use App\Modules\Fiscal\Jobs\FiscalIssueJob;
use App\Modules\Fiscal\Jobs\ProcessFiscalDocumentJob;
use App\Modules\Fiscal\Models\FiscalDocument;
use App\Modules\Fiscal\Models\TenantFiscalSettings;
use App\Modules\Inventory\Models\Store;
use App\Modules\Sales\Enums\FiscalStatusEnum;
use App\Modules\Sales\Enums\PaymentMethodEnum;
use App\Modules\Sales\Enums\PaymentStatusEnum;
use App\Modules\Sales\Enums\SaleStatusEnum;
use App\Modules\Sales\Models\PaymentTransaction;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleItem;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    $this->actingAsTenantUser();
    $this->store = Store::factory()->create();
});

describe('POST /fiscal/documents — solicitar documento fiscal', function (): void {
    it('manager cria FiscalDocument e enfileira job', function (): void {
        Queue::fake();

        $this->postJson('/api/v1/fiscal/documents', [
            'store_id' => $this->store->uuid,
            'type'     => 'nfce',
        ])->assertStatus(201)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.type', 'nfce');

        Queue::assertPushed(FiscalIssueJob::class);
    });

    it('operador não pode criar documento', function (): void {
        $this->restrictUserToPermissions(); // sem fiscal.issue

        $this->postJson('/api/v1/fiscal/documents', [
            'store_id' => $this->store->uuid,
            'type'     => 'nfce',
        ])->assertStatus(403);
    });

    it('rejeita tipo de documento inválido', function (): void {
        $this->postJson('/api/v1/fiscal/documents', [
            'store_id' => $this->store->uuid,
            'type'     => 'tipo_invalido',
        ])->assertStatus(422);
    });

    it('rejeita duplicata de documento ativo para a mesma venda', function (): void {
        Queue::fake();

        $sale = Sale::factory()->create(['store_id' => $this->store->uuid]);

        FiscalDocument::factory()->create([
            'sale_id'  => $sale->uuid,
            'type'     => FiscalDocumentTypeEnum::Nfce,
            'status'   => FiscalDocumentStatusEnum::Authorized,
        ]);

        $this->postJson('/api/v1/fiscal/documents', [
            'store_id' => $this->store->uuid,
            'sale_id'  => $sale->uuid,
            'type'     => 'nfce',
        ])->assertStatus(422);
    });
});

describe('POST /fiscal/documents/{doc}/cancel — cancelar documento', function (): void {
    it('cancela documento autorizado', function (): void {
        $document = FiscalDocument::factory()->authorized()->create([
            'store_id' => $this->store->uuid,
        ]);

        $this->postJson("/api/v1/fiscal/documents/{$document->uuid}/cancel", [
            'reason' => 'Erro de digitação',
        ])->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        expect($document->fresh()->status)->toBe(FiscalDocumentStatusEnum::Cancelled)
            ->and($document->fresh()->cancelled_at)->not->toBeNull();
    });

    it('não pode cancelar documento pendente', function (): void {
        $document = FiscalDocument::factory()->create([
            'store_id' => $this->store->uuid,
            'status'   => FiscalDocumentStatusEnum::Pending,
        ]);

        $this->postJson("/api/v1/fiscal/documents/{$document->uuid}/cancel")
            ->assertStatus(422);
    });

    it('não pode cancelar documento já cancelado', function (): void {
        $document = FiscalDocument::factory()->cancelled()->create([
            'store_id' => $this->store->uuid,
        ]);

        $this->postJson("/api/v1/fiscal/documents/{$document->uuid}/cancel")
            ->assertStatus(422);
    });
});

describe('POST /fiscal/documents/{doc}/retry — reenviar documento', function (): void {
    it('reenfileira job para documento com erro', function (): void {
        Queue::fake();

        $document = FiscalDocument::factory()->error()->create([
            'store_id' => $this->store->uuid,
        ]);

        $this->postJson("/api/v1/fiscal/documents/{$document->uuid}/retry")
            ->assertOk();

        Queue::assertPushed(ProcessFiscalDocumentJob::class);
    });

    it('não permite retry de documento autorizado', function (): void {
        Queue::fake();

        $document = FiscalDocument::factory()->authorized()->create([
            'store_id' => $this->store->uuid,
        ]);

        $this->postJson("/api/v1/fiscal/documents/{$document->uuid}/retry")
            ->assertStatus(422);

        Queue::assertNotPushed(ProcessFiscalDocumentJob::class);
    });
});

describe('GET /fiscal/documents — listagem', function (): void {
    it('lista documentos do tenant', function (): void {
        FiscalDocument::factory()->count(3)->create(['store_id' => $this->store->uuid]);

        $this->getJson('/api/v1/fiscal/documents')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('filtra por status', function (): void {
        FiscalDocument::factory()->authorized()->create(['store_id' => $this->store->uuid]);
        FiscalDocument::factory()->create(['store_id' => $this->store->uuid, 'status' => FiscalDocumentStatusEnum::Pending]);

        $this->getJson('/api/v1/fiscal/documents?status=authorized')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('não retorna documentos de outro tenant', function (): void {
        FiscalDocument::factory()->count(2)->create(['store_id' => $this->store->uuid]);

        $this->actingAsTenantUser();
        $otherStore = Store::factory()->create();
        FiscalDocument::factory()->create(['store_id' => $otherStore->uuid]);

        $this->getJson('/api/v1/fiscal/documents')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });
});

describe('ProcessFiscalDocumentJob — processamento fiscal', function (): void {
    it('stub autoriza documento e salva access_key', function (): void {
        TenantFiscalSettings::factory()->create(['tenant_id' => $this->tenant->uuid]);
        $document = FiscalDocument::factory()->create(['store_id' => $this->store->uuid]);

        $job = new \App\Modules\Fiscal\Jobs\ProcessFiscalDocumentJob(
            $document->uuid,
            $this->tenant->uuid,
        );
        $job->handle(app(\App\Modules\Fiscal\Actions\ProcessFiscalDocumentAction::class));

        $document->refresh();
        expect($document->status)->toBe(FiscalDocumentStatusEnum::Authorized)
            ->and($document->access_key)->toHaveLength(44)
            ->and($document->xml_path)->not->toBeNull()
            ->and($document->issued_at)->not->toBeNull();
    });

    it('não reprocessa documento já autorizado', function (): void {
        $document = FiscalDocument::factory()->authorized()->create(['store_id' => $this->store->uuid]);
        $originalKey = $document->access_key;

        $job = new \App\Modules\Fiscal\Jobs\ProcessFiscalDocumentJob(
            $document->uuid,
            $this->tenant->uuid,
        );
        $job->handle(app(\App\Modules\Fiscal\Actions\ProcessFiscalDocumentAction::class));

        expect($document->fresh()->access_key)->toBe($originalKey);
    });
});

describe('SaleCompleted → FiscalDocument (integração)', function (): void {
    it('NÃO enfileira job quando tenant não tem fiscal settings ativo', function (): void {
        Queue::fake();

        $sale = Sale::factory()->create([
            'store_id'       => $this->store->uuid,
            'status'         => SaleStatusEnum::Pending,
            'total_cents'    => 5000,
            'subtotal_cents' => 5000,
            'code'           => 'SAL-FISC-001',
        ]);
        SaleItem::factory()->create([
            'sale_id'            => $sale->uuid,
            'total_cents'        => 5000,
            'quantity'           => 1,
            'product_variant_id' => null,
        ]);
        PaymentTransaction::factory()->create([
            'sale_id'      => $sale->uuid,
            'method'       => PaymentMethodEnum::Cash,
            'amount_cents' => 5000,
            'status'       => PaymentStatusEnum::Paid,
        ]);

        app(\App\Modules\Sales\Actions\CompleteSaleAction::class)->execute($sale);

        // Listener é ShouldQueue — o job do listener em si é enfileirado,
        // mas nenhum ProcessFiscalDocumentJob deve existir diretamente
        Queue::assertNotPushed(ProcessFiscalDocumentJob::class);
    });

    it('sale.fiscal_status atualiza para issued após autorização', function (): void {
        $document = FiscalDocument::factory()->create([
            'store_id' => $this->store->uuid,
            'sale_id'  => null,
        ]);

        $sale = Sale::factory()->create([
            'store_id'      => $this->store->uuid,
            'fiscal_status' => FiscalStatusEnum::Pending,
        ]);

        // Atualiza o documento para ter sale_id
        $document->update(['sale_id' => $sale->uuid]);

        // Dispara o evento diretamente (simula conclusão do job)
        \App\Modules\Fiscal\Events\FiscalDocumentAuthorized::dispatch(
            $document->fresh()->update([
                'status'     => FiscalDocumentStatusEnum::Authorized,
                'access_key' => str_pad('1', 44, '0', STR_PAD_LEFT),
                'issued_at'  => now(),
            ]) ? $document->fresh() : $document->fresh()
        );

        // O listener é síncrono apenas em testes (sem queue)
        // Verificar que o padrão de atualização está correto no listener
        $listener = new \App\Modules\Sales\Listeners\UpdateSaleFiscalStatusListener();
        $listener->handleAuthorized(
            new \App\Modules\Fiscal\Events\FiscalDocumentAuthorized($document->fresh())
        );

        expect($sale->fresh()->fiscal_status)->toBe(FiscalStatusEnum::Issued);
    });
});
