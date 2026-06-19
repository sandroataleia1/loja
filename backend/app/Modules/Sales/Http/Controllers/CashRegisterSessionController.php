<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Controllers;

use App\Modules\Sales\Actions\CloseCashRegisterSessionAction;
use App\Modules\Sales\Actions\CreateSupplyAction;
use App\Modules\Sales\Actions\CreateWithdrawalAction;
use App\Modules\Sales\Actions\OpenCashRegisterSessionAction;
use App\Modules\Sales\DTOs\CloseSessionDTO;
use App\Modules\Sales\DTOs\CreateMovementDTO;
use App\Modules\Sales\DTOs\OpenSessionDTO;
use App\Modules\Sales\Http\Requests\CloseSessionRequest;
use App\Modules\Sales\Http\Requests\CreateMovementRequest;
use App\Modules\Sales\Http\Requests\OpenSessionRequest;
use App\Modules\Sales\Http\Resources\CashMovementResource;
use App\Modules\Sales\Http\Resources\CashRegisterSessionResource;
use App\Modules\Sales\Models\CashRegisterSession;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

final class CashRegisterSessionController extends Controller
{
    use HasApiResponse;

    public function index(Request $request): JsonResponse
    {
        $sessions = CashRegisterSession::query()
            ->with(['store', 'user', 'cashRegister'])
            ->when($request->string('store_id')->value(),          fn ($q, $v) => $q->where('store_id', $v))
            ->when($request->string('status')->value(),            fn ($q, $v) => $q->where('status', $v))
            ->when($request->string('user_id')->value(),           fn ($q, $v) => $q->where('user_id', $v))
            ->when($request->string('cash_register_id')->value(),  fn ($q, $v) => $q->where('cash_register_id', $v))
            ->latest('opened_at')
            ->paginate($request->integer('per_page', 20));

        return $this->success(
            data: CashRegisterSessionResource::collection($sessions),
            meta: [
                'current_page' => $sessions->currentPage(),
                'per_page'     => $sessions->perPage(),
                'total'        => $sessions->total(),
                'last_page'    => $sessions->lastPage(),
            ],
        );
    }

    public function open(OpenSessionRequest $request, OpenCashRegisterSessionAction $action): JsonResponse
    {
        $this->authorize('open', CashRegisterSession::class);

        $session = $action->execute(OpenSessionDTO::fromRequest($request));

        return $this->created(new CashRegisterSessionResource($session));
    }

    public function show(CashRegisterSession $cashRegisterSession): JsonResponse
    {
        $cashRegisterSession->load(['store', 'user', 'cashRegister', 'closedByUser', 'movements']);

        return $this->success(new CashRegisterSessionResource($cashRegisterSession));
    }

    public function close(
        CloseSessionRequest            $request,
        CashRegisterSession            $cashRegisterSession,
        CloseCashRegisterSessionAction $action,
    ): JsonResponse {
        $this->authorize('close', $cashRegisterSession);

        $session = $action->execute($cashRegisterSession, CloseSessionDTO::fromRequest($request));

        return $this->success(new CashRegisterSessionResource($session), 'Caixa fechado com sucesso.');
    }

    public function withdrawal(
        CreateMovementRequest  $request,
        CashRegisterSession    $cashRegisterSession,
        CreateWithdrawalAction $action,
    ): JsonResponse {
        $movement = $action->execute($cashRegisterSession, CreateMovementDTO::fromRequest($request));

        return $this->created(new CashMovementResource($movement), 'Sangria registrada com sucesso.');
    }

    public function supply(
        CreateMovementRequest $request,
        CashRegisterSession   $cashRegisterSession,
        CreateSupplyAction    $action,
    ): JsonResponse {
        $movement = $action->execute($cashRegisterSession, CreateMovementDTO::fromRequest($request));

        return $this->created(new CashMovementResource($movement), 'Suprimento registrado com sucesso.');
    }

    public function movements(CashRegisterSession $cashRegisterSession, Request $request): JsonResponse
    {
        $movements = $cashRegisterSession->movements()
            ->when($request->string('type')->value(), fn ($q, $v) => $q->where('type', $v))
            ->latest('created_at')
            ->get();

        return $this->success(CashMovementResource::collection($movements));
    }

    /**
     * GET /sessions/{session}/summary
     * Aggregated breakdown for the closing report: sales by payment method + movements.
     */
    public function summary(CashRegisterSession $cashRegisterSession): JsonResponse
    {
        $agg      = $cashRegisterSession->computeAggregates();
        $expected = $cashRegisterSession->computeExpectedBalanceCents();
        $saleCount = $cashRegisterSession->sales()->where('status', 'completed')->count();
        $totalSales = array_sum([
            $agg['cash_total'],
            $agg['pix_total'],
            $agg['credit_card_total'],
            $agg['debit_card_total'],
            $agg['store_credit_total'],
        ]);

        return $this->success([
            'session_uuid'    => $cashRegisterSession->uuid,
            'sale_count'      => $saleCount,
            'total_sales'     => $totalSales,
            'by_method'       => [
                'cash'         => $agg['cash_total'],
                'pix'          => $agg['pix_total'],
                'credit_card'  => $agg['credit_card_total'],
                'debit_card'   => $agg['debit_card_total'],
                'store_credit' => $agg['store_credit_total'],
            ],
            'supply_total'    => $agg['supply_total'],
            'withdrawal_total'=> $agg['withdrawal_total'],
            'expected_balance'=> $expected,
            'opening_balance' => $cashRegisterSession->opening_amount_cents,
        ]);
    }
}
