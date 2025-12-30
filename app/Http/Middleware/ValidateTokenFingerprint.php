<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Helpers\ResponseFormatter;
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
            return ResponseFormatter::error(
                null,
                'Unauthenticated',
                401
            );
        }

        $type = $token->abilities['type'] ?? null;

        // Tokens without a type are considered invalid.
        if (!in_array($type, ['access', 'refresh'], true)) {
            return ResponseFormatter::error(
                null,
                'Invalid token type',
                401
            );
        }

        /**
         * Access token: fingerprint validation (anti-hijack)
         */
        if ($type === 'access') {

            if (!isset($token->abilities['fp'])) {
                return ResponseFormatter::error(
                    null,
                    'Invalid access token',
                    401
                );
            }

            $currentFingerprint = TokenService::fingerprint($request);

            if (!hash_equals($token->abilities['fp'], $currentFingerprint)) {
                $token->delete(); // revoke immediately

                return ResponseFormatter::error(
                    null,
                    'Token hijacked',
                    401
                );
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
