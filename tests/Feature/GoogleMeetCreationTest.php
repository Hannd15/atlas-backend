<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleMeetCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_google_meet_with_valid_token_and_stored_credentials()
    {
        // Create a user with stored Google tokens
        $user = User::factory()->create([
            'google_token' => 'valid-access-token',
            'google_refresh_token' => 'valid-refresh-token',
            'google_token_expires_at' => now()->addHour(),
        ]);

        $sanctumToken = $user->createToken('test-token')->plainTextToken;

        // Mock the Google Calendar API response
        Http::fake([
            'www.googleapis.com/calendar/*' => Http::response([
                'id' => 'event123',
                'summary' => 'Team Meeting',
                'hangoutLink' => 'https://meet.google.com/abc-defg-hij',
                'htmlLink' => 'https://calendar.google.com/event?eid=abc123',
                'conferenceData' => [
                    'entryPoints' => [
                        [
                            'entryPointType' => 'video',
                            'uri' => 'https://meet.google.com/abc-defg-hij',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$sanctumToken,
        ])->postJson('/api/auth/google/meet/create', [
            'summary' => 'Team Meeting',
            'description' => 'Weekly sync',
            'start' => [
                'dateTime' => '2025-11-20T10:00:00-05:00',
                'timeZone' => 'America/New_York',
            ],
            'end' => [
                'dateTime' => '2025-11-20T11:00:00-05:00',
                'timeZone' => 'America/New_York',
            ],
            'attendees' => [
                ['email' => 'attendee@example.com'],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'id',
            'summary',
            'hangoutLink',
            'conferenceData',
        ]);
    }

    public function test_create_google_meet_rejects_invalid_token()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
        ])->postJson('/api/auth/google/meet/create', [
            'summary' => 'Test Meeting',
            'start' => [
                'dateTime' => '2025-11-20T10:00:00-05:00',
            ],
            'end' => [
                'dateTime' => '2025-11-20T11:00:00-05:00',
            ],
        ]);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_create_google_meet_validates_required_fields()
    {
        $user = User::factory()->create([
            'google_token' => 'valid-access-token',
            'google_refresh_token' => 'valid-refresh-token',
            'google_token_expires_at' => now()->addHour(),
        ]);

        $sanctumToken = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$sanctumToken,
        ])->postJson('/api/auth/google/meet/create', [
            // Missing required fields
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['summary', 'start', 'end']);
    }
}
