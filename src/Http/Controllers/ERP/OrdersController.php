<?php

namespace ERPClient\Http\Controllers\ERP;

use ERPClient\Api\ERP;
use ERPClient\Services\ERPAuthService;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class OrdersController extends Controller
{
    public function __construct(protected ERPAuthService $authService)
    {
    }

    public function index(): JsonResponse
    {
        if (! $token = $this->authService->token()) {
            return $this->unauthenticated();
        }

        $response = ERP::fetchOrders($token);

        return $this->transformOrdersResponse($response);
    }

    protected function transformOrdersResponse(?HttpResponse $response): JsonResponse
    {
        if (! $response) {
            return response()->json([
                'message' => 'Serviciul de comenzi nu este disponibil momentan.',
                'orders' => [],
            ], 502);
        }

        if ($response->status() === 401) {
            $this->authService->clear();

            return $this->unauthenticated();
        }

        if ($response->failed()) {
            return response()->json([
                'message' => $response->json('message') ?? 'Nu am putut încărca comenzile.',
                'orders' => [],
            ], $response->status());
        }

        $payload = $response->json() ?? [];

        return response()->json([
            'orders' => data_get($payload, 'orders', data_get($payload, 'data', [])),
        ]);
    }

    protected function unauthenticated(): JsonResponse
    {
        return response()->json([
            'message' => 'Autentifică-te pentru a vedea comenzile.',
        ], 401);
    }
}
