<?php

namespace ERPClient\Http\Controllers\ERP;

use ERPClient\Api\ERP;
use ERPClient\Services\ERPAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

class ReviewsController extends Controller
{
    public function __construct(protected ERPAuthService $authService)
    {
    }

    public function store(int|string $product, Request $request): JsonResponse
    {
        if (!$token = $this->authService->token()) {
            return $this->unauthenticated();
        }

        $validated = $request->validate([
            'title'   => ['required', 'string'],
            'content' => ['required', 'string'],
            'rating'  => ['required' , 'integer', 'between:1,5'],
        ]);

        $response =  ERP::storeReview(
            $token,
            $product,
            $validated['title'],
            $validated['content'],
            $validated['rating']
        );

        if (!$response) {
            return response()->json([
                'message' => 'Serviciul de adăugare recenzii nu este disponibil momentan.',
                'favorites' => [],
            ], 502);
        }

        if ($response->status() === 401) {
            $this->authService->clear();

            return $this->unauthenticated();
        }

        if (!$response->successful()) {
            return response($response->json(), $response->status());
        }

        return response($response->json());
    }

    protected function unauthenticated(): JsonResponse
    {
        return response()->json([
            'message' => 'Autentifică-te.',
        ], 401);
    }
}
