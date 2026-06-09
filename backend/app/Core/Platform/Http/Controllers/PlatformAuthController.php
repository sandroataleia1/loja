<?php

declare(strict_types=1);

namespace App\Core\Platform\Http\Controllers;

use App\Core\Platform\Models\PlatformUser;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class PlatformAuthController extends Controller
{
    use HasApiResponse;

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = PlatformUser::where('email', $request->string('email')->value())
            ->where('is_active', true)
            ->first();

        if (! $user || ! Hash::check($request->string('password')->value(), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('platform-token', ['platform:*'])->plainTextToken;

        return $this->success([
            'token' => $token,
            'user'  => [
                'uuid'  => $user->uuid,
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user('platform')?->currentAccessToken()->delete();

        return $this->noContent();
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user('platform');

        return $this->success([
            'uuid'          => $user->uuid,
            'name'          => $user->name,
            'email'         => $user->email,
            'last_login_at' => $user->last_login_at?->toISOString(),
        ]);
    }
}
