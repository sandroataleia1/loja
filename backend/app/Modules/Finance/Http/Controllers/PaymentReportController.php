<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers;

use App\Core\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class PaymentReportController extends Controller
{
    use HasApiResponse;

    /**
     * Indicadores de pagamento:
     *   - Vendas por forma de pagamento
     *   - Vendas por condição de pagamento
     *   - Total de descontos concedidos
     *   - Total de juros aplicados
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to'   => ['nullable', 'date'],
        ]);

        $tenantId = TenantContext::getIdOrFail();

        $from = $request->string('from')->value() ?: now()->startOfMonth()->toDateString();
        $to   = $request->string('to')->value() ?: now()->toDateString();

        $byMethod = $this->salesByMethod($tenantId, $from, $to);
        $byCondition = $this->salesByCondition($tenantId, $from, $to);
        $totals   = $this->totals($tenantId, $from, $to);

        return $this->success([
            'period'       => ['from' => $from, 'to' => $to],
            'by_method'    => $byMethod,
            'by_condition' => $byCondition,
            'totals'       => $totals,
        ]);
    }

    private function salesByMethod(string $tenantId, string $from, string $to): array
    {
        return DB::table('payment_transactions as pt')
            ->leftJoin('payment_methods as pm', 'pm.uuid', '=', 'pt.payment_method_id')
            ->join('sales as s', 's.uuid', '=', 'pt.sale_id')
            ->where('pt.tenant_id', $tenantId)
            ->whereBetween('s.completed_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->where('s.status', 'completed')
            ->groupBy('pt.method', 'pm.name')
            ->orderByDesc('total_amount_cents')
            ->select([
                'pt.method',
                DB::raw('COALESCE(pm.name, pt.method) as method_label'),
                DB::raw('COUNT(DISTINCT pt.sale_id) as transaction_count'),
                DB::raw('SUM(pt.amount_cents) as total_amount_cents'),
                DB::raw('SUM(pt.discount_cents) as total_discount_cents'),
                DB::raw('SUM(pt.interest_cents) as total_interest_cents'),
            ])
            ->get()
            ->map(fn ($row) => [
                'method'                => $row->method,
                'method_label'          => $row->method_label,
                'transaction_count'     => (int) $row->transaction_count,
                'total_amount_cents'    => (int) $row->total_amount_cents,
                'total_discount_cents'  => (int) $row->total_discount_cents,
                'total_interest_cents'  => (int) $row->total_interest_cents,
            ])
            ->values()
            ->all();
    }

    private function salesByCondition(string $tenantId, string $from, string $to): array
    {
        return DB::table('payment_transactions as pt')
            ->leftJoin('payment_conditions as pc', 'pc.uuid', '=', 'pt.payment_condition_id')
            ->join('sales as s', 's.uuid', '=', 'pt.sale_id')
            ->where('pt.tenant_id', $tenantId)
            ->whereBetween('s.completed_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->where('s.status', 'completed')
            ->whereNotNull('pt.payment_condition_id')
            ->groupBy('pt.payment_condition_id', 'pc.name')
            ->orderByDesc('total_amount_cents')
            ->select([
                'pt.payment_condition_id',
                DB::raw('COALESCE(pc.name, \'Não informada\') as condition_label'),
                DB::raw('COUNT(DISTINCT pt.sale_id) as transaction_count'),
                DB::raw('SUM(pt.amount_cents) as total_amount_cents'),
                DB::raw('SUM(pt.discount_cents) as total_discount_cents'),
                DB::raw('SUM(pt.interest_cents) as total_interest_cents'),
            ])
            ->get()
            ->map(fn ($row) => [
                'payment_condition_id'  => $row->payment_condition_id,
                'condition_label'       => $row->condition_label,
                'transaction_count'     => (int) $row->transaction_count,
                'total_amount_cents'    => (int) $row->total_amount_cents,
                'total_discount_cents'  => (int) $row->total_discount_cents,
                'total_interest_cents'  => (int) $row->total_interest_cents,
            ])
            ->values()
            ->all();
    }

    private function totals(string $tenantId, string $from, string $to): array
    {
        $row = DB::table('payment_transactions as pt')
            ->join('sales as s', 's.uuid', '=', 'pt.sale_id')
            ->where('pt.tenant_id', $tenantId)
            ->whereBetween('s.completed_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->where('s.status', 'completed')
            ->selectRaw('
                COUNT(DISTINCT pt.sale_id) as total_sales,
                SUM(pt.amount_cents) as total_received_cents,
                SUM(pt.discount_cents) as total_discount_cents,
                SUM(pt.interest_cents) as total_interest_cents,
                SUM(pt.fine_cents) as total_fine_cents
            ')
            ->first();

        return [
            'total_sales'           => (int) ($row->total_sales ?? 0),
            'total_received_cents'  => (int) ($row->total_received_cents ?? 0),
            'total_discount_cents'  => (int) ($row->total_discount_cents ?? 0),
            'total_interest_cents'  => (int) ($row->total_interest_cents ?? 0),
            'total_fine_cents'      => (int) ($row->total_fine_cents ?? 0),
        ];
    }
}
