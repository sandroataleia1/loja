<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Catalog\Enums\PriceListTypeEnum;
use App\Modules\Catalog\Models\PriceList;
use App\Modules\Catalog\Models\ProductPrice;
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

    public function index(): JsonResponse
    {
        $lists = PriceList::active()->currentlyValid()->orderBy('name')->get();

        return $this->success($lists->map(fn (PriceList $l) => $this->formatList($l)));
    }

    public function store(Request $request): JsonResponse
    {
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

        $list = PriceList::create($data);

        return $this->created($this->formatList($list));
    }

    public function show(PriceList $priceList): JsonResponse
    {
        return $this->success($this->formatList($priceList));
    }

    public function update(Request $request, PriceList $priceList): JsonResponse
    {
        $data = $request->validate([
            'name'                 => ['string', 'max:80'],
            'max_discount_percent' => ['numeric', 'min:0', 'max:100'],
            'is_default'           => ['boolean'],
            'is_active'            => ['boolean'],
            'valid_from'           => ['nullable', 'date'],
            'valid_to'             => ['nullable', 'date'],
        ]);

        $priceList->update($data);

        return $this->success($this->formatList($priceList->refresh()));
    }

    public function destroy(PriceList $priceList): JsonResponse
    {
        $priceList->delete();

        return $this->noContent();
    }

    /**
     * Adiciona ou atualiza preços em lote em uma tabela de preços.
     *
     * Body: [{product_id|variant_id, price_cents, min_price_cents,
     *         packaging_price_cents, packaging_qty, valid_from, valid_to}]
     */
    public function upsertPrices(Request $request, PriceList $priceList): JsonResponse
    {
        $rows = $request->validate([
            'prices'                           => ['required', 'array', 'min:1'],
            'prices.*.product_id'              => ['nullable', 'uuid'],
            'prices.*.variant_id'              => ['nullable', 'uuid'],
            'prices.*.price_cents'             => ['required', 'integer', 'min:0'],
            'prices.*.min_price_cents'         => ['nullable', 'integer', 'min:0'],
            'prices.*.packaging_price_cents'   => ['nullable', 'integer', 'min:0'],
            'prices.*.packaging_qty'           => ['nullable', 'numeric', 'min:0'],
            'prices.*.valid_from'              => ['nullable', 'date'],
            'prices.*.valid_to'                => ['nullable', 'date'],
        ])['prices'];

        $tenantId = TenantContext::getIdOrFail();

        DB::transaction(function () use ($rows, $priceList, $tenantId): void {
            foreach ($rows as $row) {
                $row['tenant_id']     = $tenantId;
                $row['price_list_id'] = $priceList->uuid;

                ProductPrice::updateOrCreate(
                    [
                        'price_list_id' => $priceList->uuid,
                        'product_id'    => $row['product_id'] ?? null,
                        'variant_id'    => $row['variant_id'] ?? null,
                    ],
                    $row,
                );
            }
        });

        return $this->success(message: count($rows) . ' preço(s) atualizados.');
    }

    public function prices(PriceList $priceList): JsonResponse
    {
        $prices = $priceList->productPrices()
            ->with(['product:uuid,code,name', 'variant:uuid,sku,name'])
            ->paginate(50);

        return $this->success($prices);
    }

    private function formatList(PriceList $l): array
    {
        return [
            'uuid'                 => $l->uuid,
            'name'                 => $l->name,
            'code'                 => $l->code,
            'type'                 => $l->type->value,
            'type_label'           => $l->type->label(),
            'currency'             => $l->currency,
            'max_discount_percent' => $l->max_discount_percent,
            'is_default'           => $l->is_default,
            'is_active'            => $l->is_active,
            'valid_from'           => $l->valid_from?->toDateString(),
            'valid_to'             => $l->valid_to?->toDateString(),
        ];
    }
}
