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

    public static function createToken(
        User $user,
        string $name,
        array $abilities,
        Request $request,
        int $days = 30
    ): string {
        $abilities['fp'] = self::fingerprint($request);

        return $user->createToken(
            name: $name,
            abilities: $abilities,
            expiresAt: now()->addDays($days)
        )->plainTextToken;
    }
}
