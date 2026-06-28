<?php

declare(strict_types=1);

namespace App\Modules\Settings\Http\Controllers;

use App\Core\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use App\Modules\Settings\Models\CustomFieldDefinition;
use App\Modules\Settings\Services\CustomFieldService;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD de definições de campos customizados.
 *
 * GET    /api/v1/custom-fields?entity_type=customer
 * POST   /api/v1/custom-fields
 * PATCH  /api/v1/custom-fields/{definition}
 * DELETE /api/v1/custom-fields/{definition}
 */
final class CustomFieldController extends Controller
{
    use HasApiResponse;

    public function __construct(private readonly CustomFieldService $service) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId   = TenantContext::getIdOrFail();
        $entityType = $request->query('entity_type');

        $query = CustomFieldDefinition::where('tenant_id', $tenantId)
            ->orderBy('entity_type')
            ->orderBy('sort_order')
            ->orderBy('label');

        if ($entityType) {
            $query->where('entity_type', $entityType);
        }

        return $this->success($query->get()->toArray());
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();

        $data = $request->validate([
            'entity_type'        => ['required', 'string', 'in:'.implode(',', CustomFieldDefinition::entityTypes())],
            'label'              => ['required', 'string', 'max:100'],
            'field_key'          => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9_]+$/'],
            'field_type'         => ['required', 'string', 'in:'.implode(',', CustomFieldDefinition::fieldTypes())],
            'options'            => ['nullable', 'array'],
            'options.*'          => ['string'],
            'is_required'        => ['nullable', 'boolean'],
            'is_searchable'      => ['nullable', 'boolean'],
            'is_visible_in_list' => ['nullable', 'boolean'],
            'sort_order'         => ['nullable', 'integer', 'min:0'],
            'placeholder'        => ['nullable', 'string', 'max:200'],
            'help_text'          => ['nullable', 'string', 'max:500'],
        ]);

        $definition = $this->service->createDefinition($tenantId, $data);

        return $this->created($definition->toArray());
    }

    public function update(Request $request, CustomFieldDefinition $definition): JsonResponse
    {
        $data = $request->validate([
            'label'              => ['sometimes', 'string', 'max:100'],
            'options'            => ['nullable', 'array'],
            'options.*'          => ['string'],
            'is_required'        => ['nullable', 'boolean'],
            'is_searchable'      => ['nullable', 'boolean'],
            'is_visible_in_list' => ['nullable', 'boolean'],
            'is_active'          => ['nullable', 'boolean'],
            'sort_order'         => ['nullable', 'integer', 'min:0'],
            'placeholder'        => ['nullable', 'string', 'max:200'],
            'help_text'          => ['nullable', 'string', 'max:500'],
        ]);

        $updated = $this->service->updateDefinition($definition, $data);

        return $this->success($updated->toArray());
    }

    public function destroy(CustomFieldDefinition $definition): JsonResponse
    {
        $definition->delete();

        return $this->noContent();
    }
}
