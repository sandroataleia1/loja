<?php

declare(strict_types=1);

namespace App\Modules\Orders\Http\Controllers;

use App\Core\Auth\Models\User;
use App\Core\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use App\Modules\Orders\Actions\ConvertQuoteToOrderAction;
use App\Modules\Orders\Actions\CreateQuoteAction;
use App\Modules\Orders\DTOs\CreateQuoteDTO;
use App\Modules\Orders\Enums\QuoteStatusEnum;
use App\Modules\Orders\Http\Requests\StoreQuoteRequest;
use App\Modules\Orders\Http\Resources\OrderResource;
use App\Modules\Orders\Http\Resources\QuoteResource;
use App\Modules\Orders\Models\Quote;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class QuoteController extends Controller
{
    use HasApiResponse;

    public function index(Request $request): JsonResponse
    {
        $quotes = Quote::where('tenant_id', TenantContext::getIdOrFail())
            ->withCount('items')
            ->with(['customer'])
            ->when($request->filled('status'),      fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->filled('q'), fn ($q) => $q->where(function ($sub) use ($request) {
                $sub->where('number', 'ilike', "%{$request->q}%");
            }))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return $this->success(
            data: QuoteResource::collection($quotes),
            meta: ['total' => $quotes->total(), 'per_page' => $quotes->perPage(), 'current_page' => $quotes->currentPage()],
        );
    }

    public function store(StoreQuoteRequest $request, CreateQuoteAction $action): JsonResponse
    {
        $quote = $action->execute(CreateQuoteDTO::fromRequest($request));
        return $this->created(new QuoteResource($quote->load('items', 'customer', 'seller')));
    }

    public function resolveSellerPin(Request $request): JsonResponse
    {
        $request->validate(['pin' => ['required', 'string', 'max:10']]);

        $hashedPin = hash('sha256', config('app.key', '') . $request->input('pin'));

        $user = User::where('tenant_id', TenantContext::getIdOrFail())
            ->where('pin', $hashedPin)
            ->where('is_active', true)
            ->first();

        if (! $user) {
            return $this->error('PIN inválido ou vendedor inativo.', 404);
        }

        return $this->success(['uuid' => $user->uuid, 'name' => $user->name]);
    }

    public function show(string $uuid): JsonResponse
    {
        $quote = Quote::where('tenant_id', TenantContext::getIdOrFail())
            ->where('uuid', $uuid)
            ->with(['items', 'customer', 'seller'])
            ->firstOrFail();

        return $this->success(new QuoteResource($quote));
    }

    public function showByNumber(string $number): JsonResponse
    {
        $quote = Quote::where('tenant_id', TenantContext::getIdOrFail())
            ->where('number', strtoupper($number))
            ->with(['items', 'customer', 'seller'])
            ->firstOrFail();

        return $this->success(new QuoteResource($quote));
    }

    public function markSent(string $uuid): JsonResponse
    {
        $quote = Quote::where('tenant_id', TenantContext::getIdOrFail())
            ->where('uuid', $uuid)->firstOrFail();

        if (! $quote->status->isEditable()) {
            return $this->error('Orçamento não pode ser marcado como enviado.', 409);
        }

        $quote->update(['status' => QuoteStatusEnum::Sent, 'sent_at' => now()]);
        return $this->success(new QuoteResource($quote->load('items', 'customer', 'seller')));
    }

    public function convert(string $uuid, Request $request, ConvertQuoteToOrderAction $action): JsonResponse
    {
        $quote = Quote::where('tenant_id', TenantContext::getIdOrFail())
            ->where('uuid', $uuid)
            ->with('items')
            ->firstOrFail();

        $order = $action->execute($quote, $request->string('expected_at')->value() ?: null);
        return $this->created(new OrderResource($order->load('items', 'customer')));
    }

    public function destroy(string $uuid): JsonResponse
    {
        $quote = Quote::where('tenant_id', TenantContext::getIdOrFail())
            ->where('uuid', $uuid)->firstOrFail();

        if (! $quote->status->isEditable()) {
            return $this->error('Apenas orçamentos em rascunho ou enviados podem ser excluídos.', 409);
        }

        $quote->delete();
        return $this->noContent();
    }
}
