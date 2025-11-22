<?php

namespace App\Http\Middleware;

use App\Models\Module;
use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Sanctum\PersonalAccessToken;

class EnsureModuleOrUserToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$allowedModules): JsonResponse|Response|RedirectResponse
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

        if ($tokenable instanceof Module) {
            if (! $tokenable->is_active) {
                return response()->json([
                    'authorized' => false,
                    'error' => 'El módulo asociado al token está inactivo.',
                ], 403);
            }

            if ($accessToken->cant('permissions:batch')) {
                return response()->json([
                    'authorized' => false,
                    'error' => 'El token del módulo no cuenta con las habilidades necesarias.',
                ], 403);
            }

            if ($allowedModules !== [] && ! in_array($tokenable->slug, $allowedModules, true)) {
                return response()->json([
                    'authorized' => false,
                    'error' => 'El módulo no tiene permisos para acceder a este recurso.',
                ], 403);
            }

            $tokenable->markUsed();
            $request->attributes->set('module', $tokenable);

            return $next($request);
        }

        if ($tokenable instanceof User) {
            $request->setUserResolver(static fn () => $tokenable);

            return $next($request);
        }

        return response()->json([
            'authorized' => false,
            'error' => 'Token inválido.',
        ], 401);
    }
}
