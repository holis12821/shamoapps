<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateTokenFingerprint
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();
        
        if (!$token || !isset($token->abilities['fp'])) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $current = hash(
            'sha256',
            $request->ip()
                . '|' . $request->userAgent()
                . '|' . config('app.key')
        );

        if (!hash_equals($token->abilities['fp'], $current)) {
            $token->delete(); // revoke immediately
            return response()->json(['message' => 'Token hijacked'], 401);
        }

        return $next($request);
    }
}
