<?php

namespace App\Http\Controllers;

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->scopes([
                'openid',
                'profile',
                'email',
                'https://www.googleapis.com/auth/calendar',
                'https://www.googleapis.com/auth/calendar.events',
            ])
            ->with([
                'access_type' => 'offline',
                'prompt' => 'consent',
            ])
            ->stateless()
            ->redirect();
    }

    public function handleGoogleCallback()
    {
        // Get user info from Google
        $googleUser = Socialite::driver('google')->stateless()->user();

        $user = User::updateOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name' => $googleUser->getName(),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
            ]
        );

        // Persist Google tokens so the application can call Google APIs server-side
        // Socialite user objects expose token, refreshToken and expiresIn when available.
        try {
            $token = $googleUser->token ?? null;
            $refresh = $googleUser->refreshToken ?? ($googleUser->refresh_token ?? null);
            $expiresIn = $googleUser->expiresIn ?? ($googleUser->expires_in ?? null);

            if ($token || $refresh || $expiresIn) {
                $user->forceFill([
                    'google_token' => $token,
                    'google_refresh_token' => $refresh,
                    'google_token_expires_at' => $expiresIn ? now()->addSeconds($expiresIn) : null,
                ])->save();
            }
        } catch (\Throwable $e) {
            logger()->error('Failed to persist google tokens: '.$e->getMessage(), ['exception' => $e]);
        }

        // Issue API token with Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        return redirect(env('FRONTEND_URL')."/login-success?token=$token");
    }
}
