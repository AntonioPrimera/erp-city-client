<?php

namespace ERPClient\Http\Controllers\ERP;

use ERPClient\Api\ERP;
use ERPClient\Http\Requests\ERPLoginRequest;
use ERPClient\Http\Requests\ERPRegisterRequest;
use ERPClient\Services\ERPAuthService;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class AuthController extends Controller
{
    public function __construct(protected ERPAuthService $authService)
    {
    }

    public function login(ERPLoginRequest $request): JsonResponse
    {
        $response = ERP::login($request->validated());

        return $this->handleAuthenticationResponse($response);
    }

    public function register(ERPRegisterRequest $request): JsonResponse
    {
        $response = ERP::register($request->validated());

        return $this->handleAuthenticationResponse($response, 201);
    }

    public function me(): JsonResponse
    {
        $token = $this->authService->token();

        if (! $token) {
            return response()->json([
                'message' => 'Autentifică-te pentru a accesa profilul.',
            ], 401);
        }

        $response = ERP::fetchProfile($token);

        if (! $response) {
            return response()->json([
                'message' => 'Serviciul ERP nu este disponibil. Incercati din nou mai tarziu.',
            ], 502);
        }

        if ($response->failed()) {
            $body = $response->json();

            return response()->json(
                $body ?? ['message' => 'Nu am putut prelua profilul.'],
                $response->status()
            );
        }

        return response()->json([
            'data' => $response->json('data') ?? $response->json(),
        ]);
    }

    public function logout(): JsonResponse
    {
        $token = $this->authService->token();

        if ($token) {
            ERP::logout($token);
        }

        $this->authService->clear();

        return response()->json(['message' => 'Logged out']);
    }

    public function session(): JsonResponse
    {
        $authenticated = $this->authService->isAuthenticated();

        return response()->json([
            'user' => $authenticated ? $this->authService->user() : null,
            'token' => $authenticated ? $this->authService->token() : null,
            'token_type' => $authenticated ? data_get($this->authService->current(), 'token_type') : null,
            'authenticated' => $authenticated,
            'expires_at' => $this->authService->expiresAt(),
        ]);
    }

    protected function handleAuthenticationResponse(?HttpResponse $response, int $successStatus = 200): JsonResponse
    {
        if (! $response) {
            return response()->json([
                'message' => 'Serviciul ERP nu este disponibil. Incercati din nou mai tarziu.',
            ], 502);
        }

        if ($response->failed()) {
            $body = $response->json();

            return response()->json(
                $body ?? ['message' => 'Autentificare esuata.'],
                $response->status()
            );
        }

        $auth = $this->authService->store($response->json() ?? []);

        $token = $auth['token'] ?? null;
        $tokenType = $auth['token_type'] ?? 'Bearer';

        $response = response()->json([
            'user' => $auth['user'] ?? null,
            'token' => $token,
            'token_type' => $tokenType,
            'expires_at' => $auth['expires_at'] ?? null,
        ], $successStatus);

        if ($token) {
            $response->header('Authorization', sprintf('%s %s', $tokenType ?? 'Bearer', $token));
        }

        return $response;
    }
}
