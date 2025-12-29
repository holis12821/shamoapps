<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use App\Helpers\ResponseFormatter;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        if ($request->expectsJson()) {

            if ($exception instanceof ApiException) {
                return ResponseFormatter::error(
                    null,
                    $exception->getMessage(),
                    $exception->getCode() ?: 400
                );
            }

            if ($exception instanceof ModelNotFoundException) {
                return ResponseFormatter::error(
                    null,
                    'Resource not found',
                    404
                );
            }

            if ($exception instanceof QueryException) {
                return ResponseFormatter::error(
                    null,
                    'Database error',
                    500
                );
            }

            if ($exception instanceof AccessDeniedHttpException) {
                return ResponseFormatter::error(
                    null,
                    'Cannot claim cart multiple times',
                    403
                );
            }

            if ($exception instanceof NotFoundHttpException) {
                return ResponseFormatter::error(
                    null,
                    'Endpoint not found',
                    404
                );
            }

            return ResponseFormatter::error(
                null,
                $exception->getMessage(),
                500
            );
        }

        return parent::render($request, $exception);
    }
}
