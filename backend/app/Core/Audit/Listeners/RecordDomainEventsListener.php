<?php

declare(strict_types=1);

namespace App\Core\Audit\Listeners;

use App\Core\Audit\DTOs\DomainEventDTO;
use App\Core\Audit\Services\DomainEventLogger;
use App\Modules\Fiscal\Events\FiscalDocumentAuthorized;
use App\Modules\Inventory\Events\StockAdjusted;
use App\Modules\Sales\Events\CashRegisterOpened;
use App\Modules\Sales\Events\SaleCancelled;
use App\Modules\Sales\Events\SaleCompleted;
use App\Modules\Sales\Events\SaleReturned;

final class RecordDomainEventsListener
{
    public function __construct(
        private readonly DomainEventLogger $domainEventLogger,
    ) {}

    public function handleSaleCompleted(SaleCompleted $event): void
    {
        $sale = $event->sale;

        $this->domainEventLogger->record(new DomainEventDTO(
            eventName: 'SaleCompleted',
            payload:   [
                'sale_uuid'   => $sale->uuid,
                'tenant_id'   => $sale->tenant_id,
                'store_id'    => $sale->store_id,
                'total_cents' => $sale->total_cents,
                'items_count' => $sale->items()->count(),
            ],
            tenantId: $sale->tenant_id,
        ));
    }

    public function handleSaleReturned(SaleReturned $event): void
    {
        $return      = $event->saleReturn;
        $originalSale = $event->originalSale;

        $this->domainEventLogger->record(new DomainEventDTO(
            eventName: 'SaleReturned',
            payload:   [
                'sale_return_uuid'  => $return->uuid,
                'original_sale_uuid' => $originalSale->uuid,
                'tenant_id'          => $originalSale->tenant_id,
                'store_id'           => $originalSale->store_id,
                'refund_total'       => $return->refund_total ?? null,
            ],
            tenantId: $originalSale->tenant_id,
        ));
    }

    public function handleSaleCancelled(SaleCancelled $event): void
    {
        $sale = $event->sale;

        $this->domainEventLogger->record(new DomainEventDTO(
            eventName: 'SaleCancelled',
            payload:   [
                'sale_uuid' => $sale->uuid,
                'tenant_id' => $sale->tenant_id,
                'store_id'  => $sale->store_id,
                'reason'    => $event->reason,
            ],
            tenantId: $sale->tenant_id,
        ));
    }

    public function handleStockAdjusted(StockAdjusted $event): void
    {
        $balance  = $event->balance;
        $movement = $event->movement;

        $this->domainEventLogger->record(new DomainEventDTO(
            eventName: 'InventoryAdjusted',
            payload:   [
                'balance_uuid'  => $balance->uuid,
                'movement_uuid' => $movement->uuid,
                'product_uuid'  => $balance->product_uuid ?? null,
                'store_id'      => $balance->store_id,
                'quantity_delta' => $movement->quantity ?? null,
            ],
            tenantId: $balance->tenant_id,
        ));
    }

    public function handleFiscalDocumentAuthorized(FiscalDocumentAuthorized $event): void
    {
        $document = $event->document;

        $this->domainEventLogger->record(new DomainEventDTO(
            eventName: 'FiscalDocumentAuthorized',
            payload:   [
                'document_uuid' => $document->uuid,
                'tenant_id'     => $document->tenant_id,
                'document_type' => $document->document_type ?? null,
                'access_key'    => $document->access_key ?? null,
            ],
            tenantId: $document->tenant_id,
        ));
    }

    public function handleCashRegisterOpened(CashRegisterOpened $event): void
    {
        $session = $event->session;

        $this->domainEventLogger->record(new DomainEventDTO(
            eventName: 'CashRegisterOpened',
            payload:   [
                'session_uuid'       => $session->uuid,
                'cash_register_uuid' => $session->cash_register_uuid ?? null,
                'store_id'           => $session->store_id ?? null,
                'opened_by'          => $session->opened_by ?? null,
            ],
            tenantId: $session->tenant_id,
        ));
    }
}
