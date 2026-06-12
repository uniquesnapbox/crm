<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyIntegrationToken
{
    public function handle(Request $request, Closure $next)
    {
        $configuredToken = (string) config('integration_api.token', '');
        $providedToken = (string) ($request->bearerToken() ?: $request->header('X-Integration-Token', ''));

        if ($configuredToken === '' || $providedToken === '' || !hash_equals($configuredToken, $providedToken)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized integration request.',
            ], 401);
        }

        return $next($request);
    }
}