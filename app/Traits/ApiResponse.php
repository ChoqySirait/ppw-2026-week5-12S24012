<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    /**
     * Membentuk respons sukses JSON standar.
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Operasi berhasil dilakukan.',
        int $statusCode = 200,
        array $headers = []
    ): JsonResponse {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        return response()->json($payload, $statusCode, $headers);
    }

    /**
     * Membentuk respons sukses koleksi Resource terpaginasi.
     */
    protected function paginatedResourceResponse(
        mixed $resourceCollection,
        string $message = 'Data berhasil diambil.',
        int $statusCode = 200
    ): JsonResponse {
        $response = $resourceCollection->response()->getData(true);

        $payload = [
            'success' => true,
            'message' => $message,
            'data' => $response['data'] ?? [],
            'meta' => $response['meta'] ?? [],
            'links' => $response['links'] ?? [],
        ];

        return response()->json($payload, $statusCode);
    }

    /**
     * Membentuk respons sukses data terpaginasi biasa.
     */
    protected function paginatedResponse(
        LengthAwarePaginator $paginator,
        string $message = 'Data berhasil diambil.',
        int $statusCode = 200,
        array $headers = []
    ): JsonResponse {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ];

        return response()->json($payload, $statusCode, $headers);
    }

    /**
     * Membentuk respons RFC 7807 Problem Details.
     */
    protected function problemResponse(
        string $title,
        string $detail,
        int $status,
        ?string $type = null,
        ?string $instance = null,
        array $extensions = []
    ): JsonResponse {
        $payload = array_merge([
            'type' => $type ?? 'https://tools.ietf.org/html/rfc7231#section-6',
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
            'instance' => $instance ?? request()->getRequestUri(),
        ], $extensions);

        return response()->json($payload, $status, [
            'Content-Type' => 'application/problem+json',
        ]);
    }
}
