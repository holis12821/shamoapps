<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Models\User;

class TokenService
{
    public static function fingerprint(Request $request): string
    {
        return hash(
            'sha256',
            $request->ip()
                . '|' . $request->userAgent()
                . '|' . config('app.key')
        );
    }

    /**
     * Access Token
     * - Short lived
     * - Fingerprinted
     */
    public static function createAccessToken(
        User $user,
        Request $request,
        int $minutes = 15
    ): string {
        return $user->createToken(
            name: 'access-token',
            abilities: [
                'type' => 'access',
                'fp' => self::fingerprint($request),
            ],
            expiresAt: now()->addMinutes($minutes)
        )->plainTextToken;
    }

    /**
     * Refresh Token
     * - Long lived
     * - Not fingerprinted
     */
    public static function createRefreshToken(
        User $user,
        int $days = 30
    ): string {
        return $user->createToken(
            name: 'refresh-token',
            abilities: [
                'type' => 'refresh',
            ],
            expiresAt: now()->addDays($days)
        )->plainTextToken;  
    }
}
