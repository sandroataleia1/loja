<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Http\Controllers;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Omnichannel\Actions\PublishProductToChannelAction;
use App\Modules\Omnichannel\Actions\UnpublishProductFromChannelAction;
use App\Modules\Omnichannel\Actions\UpdateChannelPriceAction;
use App\Modules\Omnichannel\DTOs\CreateChannelDTO;
use App\Modules\Omnichannel\DTOs\PublishProductDTO;
use App\Modules\Omnichannel\DTOs\UpdateChannelPriceDTO;
use App\Modules\Omnichannel\Enums\ChannelTypeEnum;
use App\Modules\Omnichannel\Http\Requests\CreateChannelRequest;
use App\Modules\Omnichannel\Http\Requests\PublishProductRequest;
use App\Modules\Omnichannel\Http\Requests\UpdateChannelPriceRequest;
use App\Modules\Omnichannel\Http\Resources\ChannelPriceResource;
use App\Modules\Omnichannel\Http\Resources\ChannelProductResource;
use App\Modules\Omnichannel\Http\Resources\ChannelResource;
use App\Modules\Omnichannel\Models\Channel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ChannelController
{
    // GET /channels
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Channel::class);

        $channels = Channel::where('tenant_id', TenantContext::getId())
            ->withCount('channelProducts')
            ->get();

        return ChannelResource::collection($channels);
    }

    // POST /channels
    public function store(CreateChannelRequest $request): JsonResponse
    {
        $this->authorize('create', Channel::class);

        $channel = Channel::create([
            'tenant_id' => TenantContext::getId(),
            'name'      => $request->validated('name'),
            'type'      => ChannelTypeEnum::from($request->validated('type')),
            'is_active' => $request->boolean('is_active', true),
            'store_id'  => $request->validated('store_id'),
            'metadata'  => $request->validated('metadata'),
        ]);

        return response()->json([
            'success' => true,
            'data'    => ChannelResource::make($channel),
        ], 201);
    }

    // GET /channels/{channel}
    public function show(Channel $channel): JsonResponse
    {
        $this->authorize('view', $channel);

        return response()->json([
            'success' => true,
            'data'    => ChannelResource::make($channel->load('channelProducts')),
        ]);
    }

    // POST /channels/{channel}/products
    public function publishProduct(
        PublishProductRequest       $request,
        Channel                     $channel,
        PublishProductToChannelAction $action,
    ): JsonResponse {
        $this->authorize('publish', $channel);

        $channelProduct = $action->execute(new PublishProductDTO(
            tenantId:          TenantContext::getId(),
            channelId:         $channel->uuid,
            productId:         $request->validated('product_id'),
            metadataOverrides: $request->validated('metadata_overrides'),
        ));

        return response()->json([
            'success' => true,
            'data'    => ChannelProductResource::make($channelProduct->load('channel')),
        ], 202);  // 202 Accepted — publication is async
    }

    // DELETE /channels/{channel}/products/{productId}
    public function unpublishProduct(
        Channel                          $channel,
        string                           $productId,
        UnpublishProductFromChannelAction $action,
    ): JsonResponse {
        $this->authorize('publish', $channel);

        $action->execute($channel->uuid, $productId, TenantContext::getId());

        return response()->json(['success' => true]);
    }

    // PUT /channels/{channel}/prices
    public function updatePrice(
        UpdateChannelPriceRequest $request,
        Channel                   $channel,
        UpdateChannelPriceAction  $action,
    ): JsonResponse {
        $this->authorize('update', $channel);

        $price = $action->execute(new UpdateChannelPriceDTO(
            tenantId:        TenantContext::getId(),
            channelId:       $channel->uuid,
            productId:       $request->validated('product_id'),
            price:           (float) $request->validated('price'),
            promotionalPrice: $request->validated('promotional_price') !== null
                ? (float) $request->validated('promotional_price')
                : null,
            startsAt:        $request->validated('starts_at')
                ? new \DateTimeImmutable($request->validated('starts_at'))
                : null,
            endsAt:          $request->validated('ends_at')
                ? new \DateTimeImmutable($request->validated('ends_at'))
                : null,
        ));

        return response()->json([
            'success' => true,
            'data'    => ChannelPriceResource::make($price),
        ]);
    }

    private function authorize(string $ability, mixed $model): void
    {
        app('gate')->authorize($ability, $model);
    }
}
