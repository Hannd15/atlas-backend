<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleCalendarService
{
    public function __construct(public User $user) {}

    protected function tokenExpiresSoon(): bool
    {
        if (! $this->user->google_token_expires_at) {
            return true;
        }

        // Consider tokens expiring within 60 seconds as expired
        return $this->user->google_token_expires_at->lt(now()->addSeconds(60));
    }

    public function getAccessToken(): ?string
    {
        if (! $this->user->google_token) {
            return null;
        }

        if ($this->tokenExpiresSoon() && $this->user->google_refresh_token) {
            $this->refreshAccessToken();
        }

        return $this->user->google_token;
    }

    public function refreshAccessToken(): ?string
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $refreshToken = $this->user->google_refresh_token;

        if (! $clientId || ! $clientSecret || ! $refreshToken) {
            return null;
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        $accessToken = $data['access_token'] ?? null;
        $expiresIn = $data['expires_in'] ?? null;
        $newRefresh = $data['refresh_token'] ?? null; // Google may or may not return a new refresh token

        if ($accessToken) {
            $this->user->google_token = $accessToken;
            if ($newRefresh) {
                $this->user->google_refresh_token = $newRefresh;
            }
            if ($expiresIn) {
                $this->user->google_token_expires_at = now()->addSeconds($expiresIn);
            }
            $this->user->save();

            return $accessToken;
        }

        return null;
    }

    /**
     * Perform a request against the Google Calendar API on behalf of the user.
     *
     * @param  string  $method  HTTP method (GET, POST, PUT, DELETE)
     * @param  string  $path  API path relative to https://www.googleapis.com/calendar/v3
     * @param  array<mixed>  $options  Optional options: ['query'=>[], 'json'=>[], 'headers'=>[]]
     */
    public function apiRequest(string $method, string $path, array $options = []): Response
    {
        $accessToken = $this->getAccessToken();

        if (! $accessToken) {
            abort(401, 'No access token available for this user.');
        }

        $url = 'https://www.googleapis.com/calendar/v3'.Str::start($path, '/');

        $client = Http::withHeaders(array_merge(['Authorization' => 'Bearer '.$accessToken], $options['headers'] ?? []));

        if (isset($options['query'])) {
            $client = $client->acceptJson()->retry(1, 100)->withOptions(['query' => $options['query']]);
        }

        if (isset($options['json'])) {
            $response = $client->{$this->lowerMethod($method)}($url, $options['json']);
        } else {
            $response = $client->{$this->lowerMethod($method)}($url);
        }

        // If unauthorized, attempt one refresh and retry
        if ($response->status() === 401 && $this->user->google_refresh_token) {
            $this->refreshAccessToken();
            $accessToken = $this->user->google_token;
            $client = Http::withHeaders(array_merge(['Authorization' => 'Bearer '.$accessToken], $options['headers'] ?? []));

            if (isset($options['json'])) {
                $response = $client->{$this->lowerMethod($method)}($url, $options['json']);
            } else {
                $response = $client->{$this->lowerMethod($method)}($url);
            }
        }

        return $response;
    }

    protected function lowerMethod(string $method): string
    {
        return strtolower($method);
    }
}
