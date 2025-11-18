<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleOAuthCallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_google_callback_persists_tokens()
    {
        // Prepare fake Socialite user
        $socialiteUser = new SocialiteUser;
        $socialiteUser->id = 'google-123';
        $socialiteUser->name = 'Test User';
        $socialiteUser->email = 'test@example.com';
        $socialiteUser->avatar = 'https://example.com/avatar.png';
        // Tokens available as properties on the Socialite user
        $socialiteUser->token = 'access-token-abc';
        $socialiteUser->refreshToken = 'refresh-token-xyz';
        $socialiteUser->expiresIn = 3600;

        // Mock Socialite driver chain
        $driverMock = \Mockery::mock();
        $driverMock->shouldReceive('stateless')->andReturnSelf();
        $driverMock->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($driverMock);

        // Call the controller action through the existing route
        $response = $this->get('/auth/callback');

        $response->assertStatus(302);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'google_id' => 'google-123',
            'google_token' => 'access-token-abc',
            'google_refresh_token' => 'refresh-token-xyz',
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user->google_token_expires_at);
    }
}
