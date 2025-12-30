<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ApiException;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Services\TokenService;
use Illuminate\Http\Request;

class RefreshTokenController extends Controller
{
    public function __invoke(Request $request)
    {
        $token = $request->user()->currentAccessToken();

        if (!$token || ($token->abilities['type'] ?? null) !== 'refresh') {
           throw new ApiException('Invalid refresh token', 403);
        }

        // rotate refresh token
        $token->delete();

        return ResponseFormatter::success([
            'access_token' => TokenService::createAccessToken(
                $request->user(),
                $request
            ),
            'token_type' => 'Bearer',
        ], 'Token refreshed');
    }
}
