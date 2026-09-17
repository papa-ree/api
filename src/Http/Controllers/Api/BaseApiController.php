<?php

namespace Bale\Api\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;

abstract class BaseApiController extends Controller
{
    protected function jsonResponse(mixed $data = [], int $status = 200, array $headers = []): JsonResponse
    {
        return response()->json([
            'data' => $data,
        ], $status, $headers);
    }

    protected function jsonError(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        $payload = [
            'message' => $message,
        ];

        if (! empty($errors)) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }

    protected function jsonPaginated(
        LengthAwarePaginator $paginator,
        int $status = 200,
        array $headers = [],
    ): JsonResponse {
        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ], $status, $headers);
    }
}
