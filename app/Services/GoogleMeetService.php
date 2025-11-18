<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GoogleMeetService
{
    public function __construct(public User $user) {}

    protected function tokenExpiresSoon(): bool
    {
        if (! $this->user->google_token_expires_at) {
            return true;
        }

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
        $newRefresh = $data['refresh_token'] ?? null;

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
     * Create a Google Meet meeting by creating a Calendar event with conferenceData.
     *
     * @param  array<string, mixed>  $eventData  Event data (summary, start, end, description, etc.)
     */
    public function createMeeting(array $eventData): Response
    {
        $accessToken = $this->getAccessToken();

        if (! $accessToken) {
            abort(401, 'No access token available for this user.');
        }

        // Google Meet meetings are created via Calendar API by adding conferenceData
        $payload = array_merge($eventData, [
            'conferenceData' => [
                'createRequest' => [
                    'requestId' => uniqid('meet_', true),
                    'conferenceSolutionKey' => [
                        'type' => 'hangoutsMeet',
                    ],
                ],
            ],
        ]);

        $url = 'https://www.googleapis.com/calendar/v3/calendars/primary/events?conferenceDataVersion=1';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$accessToken,
            'Content-Type' => 'application/json',
        ])->post($url, $payload);

        // If unauthorized, attempt one refresh and retry
        if ($response->status() === 401 && $this->user->google_refresh_token) {
            $this->refreshAccessToken();
            $accessToken = $this->user->google_token;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/json',
            ])->post($url, $payload);
        }

        return $response;
    }
}
