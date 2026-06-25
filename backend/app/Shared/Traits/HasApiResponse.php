<?php

declare(strict_types=1);

namespace App\Shared\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;

trait HasApiResponse
{
    protected function success(
        mixed $data = null,
        string $message = '',
        array $meta = [],
        int $status = Response::HTTP_OK,
    ): JsonResponse {
        $payload = ['success' => true];

        if ($message !== '') {
            $payload['message'] = $message;
        }

        if ($data instanceof JsonResource || $data instanceof ResourceCollection) {
            $payload['data'] = $data;
        } elseif ($data !== null) {
            $payload['data'] = $data;
        }

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    protected function created(mixed $data = null, string $message = 'Resource created successfully.'): JsonResponse
    {
        return $this->success($data, $message, status: Response::HTTP_CREATED);
    }

    protected function noContent(): JsonResponse
    {
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    protected function accepted(string $message = 'Accepted.'): JsonResponse
    {
        return $this->success(message: $message, status: Response::HTTP_ACCEPTED);
    }

    protected function notFound(string $message = 'Resource not found.'): JsonResponse
    {
        return $this->error($message, status: Response::HTTP_NOT_FOUND);
    }

    protected function error(
        string $message,
        array $errors = [],
        int $status = Response::HTTP_BAD_REQUEST,
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }
}
