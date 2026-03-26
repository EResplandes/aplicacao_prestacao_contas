<?php

namespace App\Support\Api;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApiResponse
{
    public static function success(mixed $data = null, string $message = 'Operacao realizada com sucesso.', int $status = 200, array $meta = [], array $headers = []): JsonResponse
    {
        $payload = ['message' => $message];

        if ($data !== null) {
            $payload['data'] = $data;
        }

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status, $headers);
    }

    public static function error(string $message, int $status = 400, array $errors = [], array $headers = []): JsonResponse
    {
        $payload = ['message' => $message];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status, $headers);
    }

    public static function paginated(AnonymousResourceCollection $resource, LengthAwarePaginator $paginator, string $message = 'Consulta realizada com sucesso.'): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $resource->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
