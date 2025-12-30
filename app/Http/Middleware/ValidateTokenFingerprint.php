<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\TokenService;

class ValidateTokenFingerprint
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if (!$user || !$token) {
           throw new ApiException('Unauthenticated', 401);
        }

        $type = $token->abilities['type'] ?? null;

        // Tokens without a type are considered invalid.
        if (!in_array($type, ['access', 'refresh'], true)) {
            throw new ApiException('Invalid token type', 401);
        }

        /**
         * Access token: fingerprint validation (anti-hijack)
         */
        if ($type === 'access') {

            if (!isset($token->abilities['fp'])) {
                throw new ApiException('Invalid access token',401);
            }

            $currentFingerprint = TokenService::fingerprint($request);

            if (!hash_equals($token->abilities['fp'], $currentFingerprint)) {
                $token->delete(); // revoke immediately

                throw new ApiException('Token hijacked', 401);
            }
        }

        /**
         * Refresh token:
         * - NO fingerprint check
         * - Only type validation
         */

        return $next($request);
    }
}
