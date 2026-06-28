<?php

declare(strict_types=1);

namespace App\Modules\Customers\DTOs;

use Illuminate\Support\Carbon;

final readonly class CustomerFinancialSummaryDTO
{
    public function __construct(
        public int $open_invoices_count,
        public int $open_invoices_total_cents,
        public int $paid_invoices_count,
        public int $paid_invoices_total_cents,
        public int $overdue_invoices_count,
        public int $overdue_invoices_total_cents,
        public int $overdue_paid_invoices_count,
        public int $overdue_paid_invoices_total_cents,
        public ?Carbon $last_purchase_at,
        public ?string $last_purchase_invoice_number,
        public int $last_purchase_value_cents,
        public int $highest_purchase_value_cents,
        public ?string $highest_purchase_invoice_number,
        public ?Carbon $highest_purchase_at,
        public int $avg_ticket_cents,
        public int $days_without_purchase,
        public int $credit_limit_cents,
        public int $credit_used_cents,
        public int $credit_available_cents,
    ) {}

    public static function empty(int $creditLimitCents = 0): self
    {
        return new self(
            open_invoices_count: 0,
            open_invoices_total_cents: 0,
            paid_invoices_count: 0,
            paid_invoices_total_cents: 0,
            overdue_invoices_count: 0,
            overdue_invoices_total_cents: 0,
            overdue_paid_invoices_count: 0,
            overdue_paid_invoices_total_cents: 0,
            last_purchase_at: null,
            last_purchase_invoice_number: null,
            last_purchase_value_cents: 0,
            highest_purchase_value_cents: 0,
            highest_purchase_invoice_number: null,
            highest_purchase_at: null,
            avg_ticket_cents: 0,
            days_without_purchase: 0,
            credit_limit_cents: $creditLimitCents,
            credit_used_cents: 0,
            credit_available_cents: $creditLimitCents,
        );
    }

    public function toArray(): array
    {
        return [
            'open_invoices_count'               => $this->open_invoices_count,
            'open_invoices_total_cents'          => $this->open_invoices_total_cents,
            'paid_invoices_count'                => $this->paid_invoices_count,
            'paid_invoices_total_cents'          => $this->paid_invoices_total_cents,
            'overdue_invoices_count'             => $this->overdue_invoices_count,
            'overdue_invoices_total_cents'       => $this->overdue_invoices_total_cents,
            'overdue_paid_invoices_count'        => $this->overdue_paid_invoices_count,
            'overdue_paid_invoices_total_cents'  => $this->overdue_paid_invoices_total_cents,
            'last_purchase_at'                   => $this->last_purchase_at?->toDateString(),
            'last_purchase_invoice_number'       => $this->last_purchase_invoice_number,
            'last_purchase_value_cents'          => $this->last_purchase_value_cents,
            'highest_purchase_value_cents'       => $this->highest_purchase_value_cents,
            'highest_purchase_invoice_number'    => $this->highest_purchase_invoice_number,
            'highest_purchase_at'                => $this->highest_purchase_at?->toDateString(),
            'avg_ticket_cents'                   => $this->avg_ticket_cents,
            'days_without_purchase'              => $this->days_without_purchase,
            'credit_limit_cents'                 => $this->credit_limit_cents,
            'credit_used_cents'                  => $this->credit_used_cents,
            'credit_available_cents'             => $this->credit_available_cents,
        ];
    }
}
