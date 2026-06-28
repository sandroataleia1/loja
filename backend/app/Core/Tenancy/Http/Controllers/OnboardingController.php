<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Http\Controllers;

use App\Core\Tenancy\Enums\TenantProfileEnum;
use App\Core\Tenancy\Services\TenantContext;
use App\Core\Tenancy\Services\TenantProfileService;
use App\Http\Controllers\Controller;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Onboarding do tenant — aplica perfil inicial de configurações.
 *
 * GET  /api/v1/onboarding/profiles  → lista perfis disponíveis
 * POST /api/v1/onboarding/profile   → aplica perfil ao tenant atual
 */
final class OnboardingController extends Controller
{
    use HasApiResponse;

    public function __construct(private readonly TenantProfileService $profileService) {}

    public function profiles(): JsonResponse
    {
        return $this->success($this->profileService->availableProfiles());
    }

    public function applyProfile(Request $request): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();

        $data = $request->validate([
            'profile' => ['required', 'string', 'in:'.implode(',', array_column(TenantProfileEnum::cases(), 'value'))],
        ]);

        $profile   = TenantProfileEnum::from($data['profile']);
        $enabledBy = $request->user()?->uuid;
        $result    = $this->profileService->apply($tenantId, $profile, $enabledBy);

        return $this->success($result);
    }
}
