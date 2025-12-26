<?php

namespace App\Traits;
use Illuminate\Http\Request;

trait HasTokenFingerprint
{
    public function tokenFingerprintValid(Request $request): bool
    {
        $token = $request->user()?->currentAccessToken();

        if (!$token || !isset($token->abilities['fp'])) {
            return false;
        }

        $current = hash(
            'sha256',
            $request->ip()
                . '|' . $request->userAgent()
                . '|' . config('app.key')
        );

        return hash_equals($token->abilities['fp'], $current);
    }
}