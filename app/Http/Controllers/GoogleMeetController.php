<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GoogleMeetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class GoogleMeetController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/auth/google/meet/create",
     *     summary="Create a Google Meet meeting on behalf of the authenticated user",
     *     description="Creates a Calendar event with Google Meet conferencing. Requires the user to have authorized Calendar + Meet scopes.",
     *     tags={"Google Meet"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Meeting details",
     *
     *         @OA\JsonContent(
     *             required={"summary","start","end"},
     *
     *             @OA\Property(
     *                 property="summary",
     *                 type="string",
     *                 description="Meeting title",
     *                 example="Team Standup"
     *             ),
     *             @OA\Property(
     *                 property="description",
     *                 type="string",
     *                 description="Meeting description",
     *                 example="Daily standup meeting"
     *             ),
     *             @OA\Property(
     *                 property="start",
     *                 type="object",
     *                 description="Start time",
     *                 @OA\Property(property="dateTime", type="string", format="date-time", example="2025-11-20T10:00:00-05:00"),
     *                 @OA\Property(property="timeZone", type="string", example="America/New_York")
     *             ),
     *             @OA\Property(
     *                 property="end",
     *                 type="object",
     *                 description="End time",
     *                 @OA\Property(property="dateTime", type="string", format="date-time", example="2025-11-20T11:00:00-05:00"),
     *                 @OA\Property(property="timeZone", type="string", example="America/New_York")
     *             ),
     *             @OA\Property(
     *                 property="attendees",
     *                 type="array",
     *                 description="Optional list of attendees",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="email", type="string", example="attendee@example.com")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Meeting created successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="id", type="string", example="abc123def456"),
     *             @OA\Property(property="summary", type="string", example="Team Standup"),
     *             @OA\Property(property="hangoutLink", type="string", example="https://meet.google.com/abc-defg-hij"),
     *             @OA\Property(property="htmlLink", type="string", example="https://www.google.com/calendar/event?eid=..."),
     *             @OA\Property(
     *                 property="conferenceData",
     *                 type="object",
     *                 @OA\Property(
     *                     property="entryPoints",
     *                     type="array",
     *
     *                     @OA\Items(
     *
     *                         @OA\Property(property="entryPointType", type="string", example="video"),
     *                         @OA\Property(property="uri", type="string", example="https://meet.google.com/abc-defg-hij")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated or no Google tokens"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function create(Request $request)
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
            Log::warning('Google Meet create attempted for non-user tokenable.', ['type' => $accessToken->tokenable_type]);

            return response()->json(['error' => 'Token inválido.'], 401);
        }

        $validated = $request->validate([
            'summary' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start' => 'required|array',
            'start.dateTime' => 'required|date',
            'start.timeZone' => 'nullable|string',
            'end' => 'required|array',
            'end.dateTime' => 'required|date|after:start.dateTime',
            'end.timeZone' => 'nullable|string',
            'attendees' => 'nullable|array',
            'attendees.*.email' => 'required|email',
        ]);

        $service = new GoogleMeetService($tokenable);

        try {
            $response = $service->createMeeting($validated);

            if ($response->successful()) {
                return response()->json($response->json(), 200);
            }

            return response()->json([
                'error' => 'Error al crear la reunión en Google Meet.',
                'details' => $response->json(),
            ], $response->status());
        } catch (\Exception $e) {
            Log::error('Error creating Google Meet: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['error' => 'Error al crear la reunión.'], 500);
        }
    }
}
