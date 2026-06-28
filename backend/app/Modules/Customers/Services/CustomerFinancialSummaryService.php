<?php

declare(strict_types=1);

namespace App\Modules\Customers\Services;

use App\Modules\Customers\DTOs\CustomerFinancialSummaryDTO;
use App\Modules\Customers\Models\Customer;
use Illuminate\Support\Facades\Cache;

final class CustomerFinancialSummaryService
{
    private const TTL = 60;

    public function getSummary(Customer $customer): CustomerFinancialSummaryDTO
    {
        $cacheKey = "customer_financial:{$customer->tenant_id}:{$customer->uuid}";

        return Cache::remember($cacheKey, self::TTL, function () use ($customer): CustomerFinancialSummaryDTO {
            return $this->buildSummary($customer);
        });
    }

    public function invalidate(string $tenantId, string $customerId): void
    {
        Cache::forget("customer_financial:{$tenantId}:{$customerId}");
    }

    private function buildSummary(Customer $customer): CustomerFinancialSummaryDTO
    {
        // Módulo financeiro não implementado ainda — retorna DTO com zeros.
        // Quando o módulo financeiro existir, substituir por queries reais.
        $creditLimitCents = (int) ($customer->credit_limit_cents ?? 0);

        return CustomerFinancialSummaryDTO::empty($creditLimitCents);
    }
}
