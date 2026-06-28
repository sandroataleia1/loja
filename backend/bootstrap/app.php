<?php

declare(strict_types=1);

use App\Core\Audit\Http\Middleware\AttachCorrelationIdMiddleware;
use App\Core\Auth\Http\Middleware\RequirePermission;
use App\Core\Auth\Http\Middleware\RequireRole;
use App\Core\Auth\Http\Middleware\RequireStoreAccess;
use App\Core\Tenancy\Http\Middleware\ResolveTenant;
use App\Http\Middleware\RequireFeature;
use App\Shared\Exceptions\AppException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant'       => ResolveTenant::class,
            'permission'   => RequirePermission::class,
            'role'         => RequireRole::class,
            'store_access' => RequireStoreAccess::class,
            'feature'      => RequireFeature::class,
        ]);

        $middleware->appendToGroup('api', AttachCorrelationIdMiddleware::class);

        // ResolveTenant DEVE rodar depois do auth (precisa do usuário) e ANTES do
        // SubstituteBindings — senão o route-model binding de modelos escopados
        // (ex.: customers/{customer}) executa sem contexto de tenant e o
        // TenantScope (fail-closed) lança TenantContextMissingException (500).
        $middleware->priority([
            \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class,
            \Illuminate\Routing\Middleware\ThrottleRequestsWithRedis::class,
            \Illuminate\Contracts\Session\Middleware\AuthenticatesSessions::class,
            ResolveTenant::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \Illuminate\Auth\Middleware\Authorize::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        /*
        |----------------------------------------------------------------------
        | Exceções de domínio — BusinessException, NotFoundException, etc.
        | Renderizam diretamente via render() com o padrão da API.
        |----------------------------------------------------------------------
        */
        $exceptions->render(function (AppException $e, Request $request): \Illuminate\Http\JsonResponse {
            return $e->render();
        });

        /*
        |----------------------------------------------------------------------
        | Erros de validação do FormRequest
        |----------------------------------------------------------------------
        */
        $exceptions->render(function (ValidationException $e, Request $request): \Illuminate\Http\JsonResponse {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Os dados informados são inválidos.',
                    'errors'  => $e->errors(),
                ], 422);
            }

            throw $e;
        });

        /*
        |----------------------------------------------------------------------
        | Recurso não encontrado (route model binding ou 404)
        |----------------------------------------------------------------------
        */
        $exceptions->render(function (NotFoundHttpException $e, Request $request): \Illuminate\Http\JsonResponse {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recurso não encontrado.',
                    'errors'  => [],
                ], 404);
            }

            throw $e;
        });

        /*
        |----------------------------------------------------------------------
        | Outros erros HTTP (403, 405, 429, etc.)
        |----------------------------------------------------------------------
        */
        $exceptions->render(function (HttpException $e, Request $request): \Illuminate\Http\JsonResponse {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Erro HTTP '.$e->getStatusCode().'.',
                    'errors'  => [],
                ], $e->getStatusCode());
            }

            throw $e;
        });

    })->create();
