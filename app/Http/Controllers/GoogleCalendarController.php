<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class GoogleCalendarController extends Controller
{
    /**
     * Proxy calendar requests to Google on behalf of the user associated with the provided bearer token.
     *
     * Expected JSON body:
     * {
     *   "method": "GET|POST|PUT|DELETE",
     *   "path": "/calendars/primary/events",
     *   "query": { ... },
     *   "json": { ... }
     * }
     */
    public function proxy(Request $request)
    {
        $tokenValue = $request->bearerToken();

        if (! $tokenValue) {
            return response()->json(['error' => 'Token no enviado.'], 401);
        }

        $accessToken = PersonalAccessToken::findToken($tokenValue);
        if (! $accessToken) {
            return response()->json(['error' => 'Token inválido.'], 401);
        }

        $tokenable = $accessToken->tokenable;
        if (! $tokenable instanceof User) {
            Log::warning('Google proxy attempted for non-user tokenable.', ['type' => $accessToken->tokenable_type]);

            return response()->json(['error' => 'Token inválido.'], 401);
        }

        $validated = $request->validate([
            'method' => 'required|string',
            'path' => 'required|string',
            'query' => 'nullable|array',
            'json' => 'nullable|array',
        ]);

        $service = new GoogleCalendarService($tokenable);

        try {
            $response = $service->apiRequest($validated['method'], $validated['path'], [
                'query' => $validated['query'] ?? null,
                'json' => $validated['json'] ?? null,
            ]);

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            Log::error('Error proxying google calendar request: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['error' => 'Error al realizar la solicitud al API de Google.'], 500);
        }
    }
}
