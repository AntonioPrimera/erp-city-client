<?php

namespace ERPClient\Http\Controllers\ERP;

use ERPClient\Api\ERP;
use ERPClient\Services\ERPAuthService;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class FavoritesController extends Controller
{
    public function __construct(protected ERPAuthService $authService)
    {
    }

    public function index(): JsonResponse
    {
        if (! $token = $this->authService->token()) {
            return $this->unauthenticated();
        }

        $response = ERP::fetchFavorites($token);

        return $this->transformFavoritesResponse($response);
    }

    public function toggle(int|string $product): JsonResponse
    {
        if (! $token = $this->authService->token()) {
            return $this->unauthenticated();
        }

        $response = ERP::toggleFavorite($token, $product);

        return $this->transformFavoritesResponse($response);
    }

    protected function transformFavoritesResponse(?HttpResponse $response): JsonResponse
    {
        if (! $response) {
            return response()->json([
                'message' => 'Serviciul de favorite nu este disponibil momentan.',
                'favorites' => [],
            ], 502);
        }

        if ($response->status() === 401) {
            $this->authService->clear();

            return $this->unauthenticated();
        }

        if ($response->failed()) {
            return response()->json([
                'message' => $response->json('message') ?? 'Nu am putut actualiza favoritele.',
                'favorites' => [],
            ], $response->status());
        }

        $payload = $response->json() ?? [];

        return response()->json([
            'favorites' => data_get($payload, 'favorites', data_get($payload, 'data', [])),
        ]);
    }

    protected function unauthenticated(): JsonResponse
    {
        return response()->json([
            'message' => 'Autentifică-te pentru a folosi favoritele.',
        ], 401);
    }
}
