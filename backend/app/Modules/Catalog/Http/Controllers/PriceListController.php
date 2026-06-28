<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Catalog\Enums\PriceListTypeEnum;
use App\Modules\Catalog\Http\Resources\PriceListResource;
use App\Modules\Catalog\Http\Resources\ProductPriceResource;
use App\Modules\Catalog\Http\Resources\ProductPriceHistoryResource;
use App\Modules\Catalog\Models\PriceList;
use App\Modules\Catalog\Models\ProductPrice;
use App\Modules\Catalog\Models\ProductPriceHistory;
use App\Modules\Catalog\Services\PriceResolverService;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;

final class PriceListController extends Controller
{
    use HasApiResponse;

    /**
     * Lista tabelas de preço.
     *
     * Por padrão retorna apenas ativas e vigentes.
     * Parâmetros de admin:
     *   ?include_inactive=true  — inclui listas desativadas
     *   ?include_expired=true   — inclui listas fora de vigência
     */
    public function index(Request $request): JsonResponse
    {
        $query = PriceList::query();

        if (! $request->boolean('include_inactive')) {
            $query->active();
        }

        if (! $request->boolean('include_expired')) {
            $query->currentlyValid();
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        $lists = $query->withCount('productPrices')->orderBy('name')->get();

        return $this->success(PriceListResource::collection($lists));
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();

        $data = $request->validate([
            'name'                 => ['required', 'string', 'max:80'],
            'code'                 => ['required', 'string', 'max:20'],
            'type'                 => ['required', Rule::enum(PriceListTypeEnum::class)],
            'currency'             => ['string', 'size:3'],
            'max_discount_percent' => ['numeric', 'min:0', 'max:100'],
            'is_default'           => ['boolean'],
            'valid_from'           => ['nullable', 'date'],
            'valid_to'             => ['nullable', 'date', 'after_or_equal:valid_from'],
        ]);

        $list = DB::transaction(function () use ($data, $tenantId): PriceList {
            if (! empty($data['is_default'])) {
                PriceList::where('tenant_id', $tenantId)->update(['is_default' => false]);
                app(PriceResolverService::class)->invalidateDefaultList($tenantId);
            }

            return PriceList::create($data);
        });

        return $this->created(new PriceListResource($list->loadCount('productPrices')));
    }

    public function show(PriceList $priceList): JsonResponse
    {
        return $this->success(new PriceListResource($priceList->loadCount('productPrices')));
    }

    public function update(Request $request, PriceList $priceList): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();

        $data = $request->validate([
            'name'                 => ['string', 'max:80'],
            'max_discount_percent' => ['numeric', 'min:0', 'max:100'],
            'is_default'           => ['boolean'],
            'is_active'            => ['boolean'],
            'valid_from'           => ['nullable', 'date'],
            'valid_to'             => ['nullable', 'date'],
        ]);

        $updated = DB::transaction(function () use ($data, $priceList, $tenantId): PriceList {
            if (! empty($data['is_default'])) {
                PriceList::where('tenant_id', $tenantId)
                    ->where('uuid', '!=', $priceList->uuid)
                    ->update(['is_default' => false]);
                app(PriceResolverService::class)->invalidateDefaultList($tenantId);
            }

            $priceList->update($data);

            return $priceList->refresh();
        });

        return $this->success(new PriceListResource($updated->loadCount('productPrices')));
    }

    public function destroy(PriceList $priceList): JsonResponse
    {
        if ($priceList->is_default) {
            return $this->error(
                'Não é possível excluir a lista de preço padrão. Defina outra lista como padrão antes de excluir esta.',
                status: 422,
            );
        }

        $priceList->delete();

        return $this->noContent();
    }

    /**
     * Adiciona ou atualiza preços em lote em uma tabela de preços.
     *
     * Body: { prices: [{product_id|variant_id, price_cents, min_price_cents,
     *         cost_price_cents, packaging_price_cents, packaging_qty, valid_from, valid_to, reason}] }
     *
     * Após cada upsert:
     * - Registra histórico em catalog_price_history (observer não dispara em updateOrCreate)
     * - Invalida cache Redis da variante afetada
     */
    public function upsertPrices(Request $request, PriceList $priceList): JsonResponse
    {
        $rows = $request->validate([
            'prices'                           => ['required', 'array', 'min:1'],
            'prices.*.product_id'              => ['nullable', 'uuid'],
            'prices.*.variant_id'              => ['nullable', 'uuid'],
            'prices.*.price_cents'             => ['required', 'integer', 'min:0'],
            'prices.*.min_price_cents'         => ['nullable', 'integer', 'min:0'],
            'prices.*.cost_price_cents'        => ['nullable', 'integer', 'min:0'],
            'prices.*.packaging_price_cents'   => ['nullable', 'integer', 'min:0'],
            'prices.*.packaging_qty'           => ['nullable', 'numeric', 'min:0'],
            'prices.*.valid_from'              => ['nullable', 'date'],
            'prices.*.valid_to'                => ['nullable', 'date'],
            'prices.*.reason'                  => ['nullable', 'string', 'max:255'],
        ])['prices'];

        $tenantId  = TenantContext::getIdOrFail();
        $userId    = auth()->id();
        $resolver  = app(PriceResolverService::class);

        DB::transaction(function () use ($rows, $priceList, $tenantId, $userId, $resolver): void {
            foreach ($rows as $row) {
                $key = [
                    'price_list_id' => $priceList->uuid,
                    'product_id'    => $row['product_id'] ?? null,
                    'variant_id'    => $row['variant_id'] ?? null,
                ];

                $fill = array_merge($key, [
                    'tenant_id'             => $tenantId,
                    'price_cents'           => $row['price_cents'],
                    'min_price_cents'       => $row['min_price_cents'] ?? null,
                    'cost_price_cents'      => $row['cost_price_cents'] ?? null,
                    'packaging_price_cents' => $row['packaging_price_cents'] ?? null,
                    'packaging_qty'         => $row['packaging_qty'] ?? null,
                    'valid_from'            => $row['valid_from'] ?? null,
                    'valid_to'              => $row['valid_to'] ?? null,
                ]);

                // Histórico manual (observer não dispara em updateOrCreate)
                $existing = ProductPrice::where($key)->first();
                $oldPrice = $existing?->price_cents;

                $productPrice = ProductPrice::updateOrCreate($key, $fill);

                if ($oldPrice !== $row['price_cents']) {
                    ProductPriceHistory::create([
                        'tenant_id'       => $tenantId,
                        'product_id'      => $row['product_id'] ?? null,
                        'variant_id'      => $row['variant_id'] ?? null,
                        'old_price_cents' => $oldPrice,
                        'new_price_cents' => $row['price_cents'],
                        'changed_by'      => $userId,
                        'changed_at'      => now(),
                        'reason'          => $row['reason'] ?? ($oldPrice === null ? 'Cadastro inicial' : null),
                    ]);
                }

                // Invalida cache da variante afetada
                if (! empty($row['variant_id'])) {
                    $resolver->invalidate($tenantId, $priceList->uuid, $row['variant_id']);
                }
            }
        });

        return $this->success(message: count($rows) . ' preço(s) atualizados.');
    }

    public function prices(Request $request, PriceList $priceList): JsonResponse
    {
        $query = $priceList->productPrices()
            ->with(['product:uuid,code,name', 'variant:uuid,sku,name,price_cents']);

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->string('product_id'));
        }

        if ($request->filled('variant_id')) {
            $query->where('variant_id', $request->string('variant_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search): void {
                $q->whereHas('product', fn ($pq) => $pq->where('name', 'ilike', "%{$search}%")
                    ->orWhere('code', 'ilike', "%{$search}%"))
                  ->orWhereHas('variant', fn ($vq) => $vq->where('sku', 'ilike', "%{$search}%")
                    ->orWhere('name', 'ilike', "%{$search}%"));
            });
        }

        $prices = $query->paginate((int) $request->input('per_page', 50));

        return $this->success(ProductPriceResource::collection($prices)->response()->getData(true));
    }

    public function history(Request $request, PriceList $priceList): JsonResponse
    {
        $history = ProductPriceHistory::query()
            ->where('price_list_id', $priceList->uuid)
            ->with(['product:uuid,code,name', 'variant:uuid,sku', 'changedBy:uuid,name'])
            ->orderByDesc('changed_at')
            ->paginate((int) $request->input('per_page', 30));

        return $this->success(ProductPriceHistoryResource::collection($history)->response()->getData(true));
    }
}
