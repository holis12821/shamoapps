<?php

namespace App\Helpers;

class ResponseFormatter
{
    public static function success(
        $data = null,
        string $message = 'Success',
        int $code = 200,
        array $meta = []
    ) {
        return response()->json([
            'meta' => array_merge([
                'code' => $code,
                'status' => 'success',
                'message' => $message,
            ], $meta),
            'data' => $data,
        ], $code);
    }

    public static function error(
        $data = null,
        string $message = 'Error',
        int $code = 400,
        array $meta = []
    ) {
        return response()->json([
            'meta' => array_merge([
                'code' => $code,
                'status' => 'error',
                'message' => $message,
            ], $meta),
            'data' => $data,
        ], $code);
    }
}
