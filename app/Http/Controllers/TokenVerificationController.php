<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class TokenVerificationController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/auth/token/verify",
     *     summary="Verify bearer token and optional authorization requirements",
     *     description="Validates a Sanctum bearer token and confirms whether the associated user meets the provided role and/or permission requirements.",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=false,
     *         description="Optional role and permission requirements to evaluate against the authenticated user.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="roles",
     *                 type="array",
     *                 description="Array of role names that the user must have (all must be satisfied).",
     *
     *                 @OA\Items(type="string", example="admin")
     *             ),
     *
     *             @OA\Property(
     *                 property="permissions",
     *                 type="array",
     *                 description="Array of permission names that the user must have (all must be satisfied).",
     *
     *                 @OA\Items(type="string", example="edit-users")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Token is valid and requirements satisfied.",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="authorized", type="boolean", example=true),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=15),
     *                 @OA\Property(property="name", type="string", example="María Pérez"),
     *                 @OA\Property(property="email", type="string", format="email", example="maria@example.com"),
     *                 @OA\Property(
     *                     property="roles_list",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="7")
     *                 ),
     *
     *                 @OA\Property(
     *                     property="permissions_list",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="12")
     *                 ),
     *
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             ),
     *             @OA\Property(
     *                 property="token",
     *                 type="object",
     *                 @OA\Property(property="abilities", type="array", @OA\Items(type="string", example="*") ),
     *                 @OA\Property(property="expires_at", type="string", format="date-time", nullable=true)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Token missing, invalid, or expired.",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="authorized", type="boolean", example=false),
     *             @OA\Property(property="error", type="string", example="Token inválido o expirado." )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Token valid but user lacks required roles or permissions.",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="authorized", type="boolean", example=false),
     *             @OA\Property(property="error", type="string", example="El usuario no cumple con los requisitos de autorización." )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error in provided roles or permissions.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="The roles.0 field must exist in roles."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function verify(Request $request): JsonResponse
    {
        $tokenValue = $request->bearerToken();

        if (! $tokenValue) {
            return response()->json([
                'authorized' => false,
                'error' => 'Token no enviado en la cabecera Authorization.',
            ], 401);
        }

        $accessToken = PersonalAccessToken::findToken($tokenValue);

        if (! $accessToken || ($accessToken->expires_at && $accessToken->expires_at->isPast())) {
            return response()->json([
                'authorized' => false,
                'error' => 'Token inválido o expirado.',
            ], 401);
        }

        $tokenable = $accessToken->tokenable;

        if (! $tokenable instanceof User) {
            Log::warning('Token verification attempted for unsupported tokenable.', [
                'tokenable_type' => $accessToken->tokenable_type,
                'token_id' => $accessToken->id,
            ]);

            return response()->json([
                'authorized' => false,
                'error' => 'Token inválido.',
            ], 401);
        }

        $validated = $request->validate([
            'roles' => 'nullable|array',
            'roles.*' => 'string|min:1',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|min:1',
        ]);

        $tokenable->loadMissing(['roles:id,name', 'permissions:id,name']);

        $requiredRoles = collect($validated['roles'] ?? []);
        $requiredPermissions = collect($validated['permissions'] ?? []);

        $missingRoles = $requiredRoles->diff($tokenable->roles->pluck('name'))->values();
        $missingPermissions = $requiredPermissions->diff($tokenable->permissions->pluck('name'))->values();

        if ($missingRoles->isNotEmpty() || $missingPermissions->isNotEmpty()) {
            $error = match (true) {
                $missingRoles->isNotEmpty() && $missingPermissions->isNotEmpty() => 'El usuario no cuenta con los roles y permisos requeridos.',
                $missingRoles->isNotEmpty() => 'El usuario no cuenta con todos los roles requeridos.',
                default => 'El usuario no cuenta con todos los permisos requeridos.',
            };

            return response()->json([
                'authorized' => false,
                'error' => $error,
                'missing_roles' => $missingRoles,
                'missing_permissions' => $missingPermissions,
            ], 403);
        }

        return response()->json([
            'authorized' => true,
            'user' => [
                'id' => $tokenable->id,
                'name' => $tokenable->name,
                'email' => $tokenable->email,
                'roles_list' => $tokenable->roles->pluck('id')->map(fn ($id) => (string) $id)->values(),
                'permissions_list' => $tokenable->permissions->pluck('id')->map(fn ($id) => (string) $id)->values(),
                'updated_at' => optional($tokenable->updated_at)->toISOString(),
            ],
            'token' => [
                'abilities' => $accessToken->abilities,
                'expires_at' => optional($accessToken->expires_at)?->toISOString(),
            ],
        ]);
    }
}
