<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;

trait ApiResponse
{
    protected function success(mixed $data = null, string $message = 'OK', int $status = 200, array $meta = []): JsonResponse
    {
        $payload = [
            'success' => true,
            'message' => $message,
        ];

        // Unwrap paginators into data + meta for a predictable client contract.
        if ($data instanceof AbstractPaginator) {
            $payload['data'] = $data->items();
            $payload['meta'] = $this->paginationMeta($data);
        } elseif ($data instanceof ResourceCollection) {
            $resource = $data->resource;
            $payload['data'] = $data;
            if ($resource instanceof AbstractPaginator) {
                $payload['meta'] = $this->paginationMeta($resource);
            }
        } else {
            $payload['data'] = $data;
        }

        if (! empty($meta)) {
            $payload['meta'] = array_merge($payload['meta'] ?? [], $meta);
        }

        return response()->json($payload, $status);
    }

    protected function error(string $message = 'Something went wrong', int $status = 400, array $errors = []): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if (! empty($errors)) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }

    /**
     * @return array<string, mixed>
     */
    protected function paginationMeta(AbstractPaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page'     => $paginator->perPage(),
            'total'        => method_exists($paginator, 'total') ? $paginator->total() : null,
            'last_page'    => method_exists($paginator, 'lastPage') ? $paginator->lastPage() : null,
            'from'         => $paginator->firstItem(),
            'to'           => $paginator->lastItem(),
        ];
    }
}
