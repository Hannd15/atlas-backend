<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $origin = $request->headers->get('Origin');
        $allowedOrigins = config('cors.allowed_origins', []);

        $allowedOrigin = $this->resolveAllowedOrigin($origin, $allowedOrigins);

        $headers = $this->corsHeaders($allowedOrigin);

        if ($request->getMethod() === Request::METHOD_OPTIONS) {
            return $this->applyHeaders(new SymfonyResponse(null, SymfonyResponse::HTTP_NO_CONTENT), $headers);
        }

        /** @var SymfonyResponse $response */
        $response = $next($request);

        return $this->applyHeaders($response, $headers);
    }

    /**
     * Determine if the incoming origin is allowed.
     */
    protected function resolveAllowedOrigin(?string $origin, array $allowedOrigins): ?string
    {
        if ($origin === null) {
            return null;
        }

        foreach ($allowedOrigins as $allowed) {
            if (strcasecmp($allowed, $origin) === 0) {
                return $origin;
            }
        }

        return null;
    }

    /**
     * Prepare the CORS headers for the response.
     */
    protected function corsHeaders(?string $origin): array
    {
        if ($origin === null) {
            return [];
        }

        return [
            'Access-Control-Allow-Origin' => $origin,
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Methods' => implode(',', config('cors.allowed_methods', [])),
            'Access-Control-Allow-Headers' => implode(',', config('cors.allowed_headers', [])),
        ];
    }

    /**
     * Apply the prepared headers to the response.
     */
    protected function applyHeaders(SymfonyResponse $response, array $headers): SymfonyResponse
    {
        foreach ($headers as $key => $value) {
            if ($value !== null && $value !== '') {
                $response->headers->set($key, $value);
            }
        }

        return $response;
    }
}
