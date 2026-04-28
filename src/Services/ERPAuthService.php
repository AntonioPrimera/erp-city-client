<?php

namespace ERPClient\Services;

use Carbon\Carbon;
use Exception;

class ERPAuthService
{
    protected const SESSION_KEY = 'erp_auth';

    public function store(array $payload): array
    {
        $auth = [
            'token' => (string) data_get($payload, 'token', ''),
            'token_type' => data_get($payload, 'token_type'),
            'expires_at' => data_get($payload, 'expires_at'),
            'user' => $this->sanitizeUser(data_get($payload, 'user', [])),
        ];

        session([self::SESSION_KEY => $auth]);

        return $auth;
    }

    public function get(): ?array
    {
        return session(self::SESSION_KEY);
    }

    public function current(): array
    {
        return $this->get() ?? [
            'token' => null,
            'token_type' => null,
            'expires_at' => null,
            'user' => null,
        ];
    }

    public function user(): ?array
    {
        return data_get($this->current(), 'user');
    }

    public function token(): ?string
    {
        if (! $this->isAuthenticated()) {
            return null;
        }

        $token = data_get($this->current(), 'token');

        return $token !== '' ? $token : null;
    }

    public function expiresAt(): ?string
    {
        return data_get($this->current(), 'expires_at');
    }

    public function isAuthenticated(): bool
    {
        $auth = $this->get();

        if (! $auth) {
            return false;
        }

        if ($this->tokenExpired(data_get($auth, 'expires_at'))) {
            $this->clear();

            return false;
        }

        return ! empty(data_get($auth, 'user')) && filled(data_get($auth, 'token'));
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function updateUser(array $user): array
    {
        $auth = $this->current();
        $auth['user'] = $this->sanitizeUser($user);
        session([self::SESSION_KEY => $auth]);

        return $auth;
    }

    protected function sanitizeUser(mixed $user): array
    {
        return is_array($user) ? $user : [];
    }

    protected function tokenExpired(?string $expiresAt): bool
    {
        if (! $expiresAt) {
            return false;
        }

        try {
            return Carbon::parse($expiresAt)->isPast();
        } catch (Exception) {
            return false;
        }
    }
}
