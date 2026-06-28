<?php

declare(strict_types=1);

namespace App\Modules\Financial\Http\Controllers;

use App\Core\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use App\Modules\Financial\Enums\CollectionActionEnum;
use App\Modules\Financial\Models\CollectionRule;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD de regras de cobrança automática.
 *
 * GET    /api/v1/collection-rules
 * POST   /api/v1/collection-rules
 * PATCH  /api/v1/collection-rules/{rule}
 * DELETE /api/v1/collection-rules/{rule}
 */
final class CollectionRuleController extends Controller
{
    use HasApiResponse;

    private function actionValues(): string
    {
        return implode(',', array_column(CollectionActionEnum::cases(), 'value'));
    }

    public function index(): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();

        $rules = CollectionRule::where('tenant_id', $tenantId)
            ->orderBy('trigger_days')
            ->get();

        return $this->success($rules->toArray());
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();

        $data = $request->validate([
            'name'             => ['required', 'string', 'max:80'],
            'trigger_days'     => ['required', 'integer', 'min:1', 'max:365'],
            'action_type'      => ['required', 'string', 'in:'.$this->actionValues()],
            'message_template' => ['nullable', 'string', 'max:1000'],
            'is_active'        => ['nullable', 'boolean'],
            'sort_order'       => ['nullable', 'integer', 'min:0'],
        ]);

        $data['tenant_id'] = $tenantId;
        $rule = CollectionRule::create($data);

        return $this->created($rule->toArray());
    }

    public function update(Request $request, CollectionRule $rule): JsonResponse
    {
        $data = $request->validate([
            'name'             => ['sometimes', 'string', 'max:80'],
            'trigger_days'     => ['sometimes', 'integer', 'min:1', 'max:365'],
            'action_type'      => ['sometimes', 'string', 'in:'.$this->actionValues()],
            'message_template' => ['nullable', 'string', 'max:1000'],
            'is_active'        => ['nullable', 'boolean'],
            'sort_order'       => ['nullable', 'integer', 'min:0'],
        ]);

        $rule->update($data);

        return $this->success($rule->fresh()->toArray());
    }

    public function destroy(CollectionRule $rule): JsonResponse
    {
        $rule->delete();

        return $this->noContent();
    }
}
