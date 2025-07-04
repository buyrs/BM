<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Inertia\Inertia;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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

        $this->renderable(function (Throwable $e) {
            if (request()->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage()
                ], 500);
            }

            if (request()->header('X-Inertia')) {
                return Inertia::render('Error', [
                    'status' => $e->getCode() ?: 500,
                    'message' => $e->getMessage()
                ]);
            }
        });
    }
} 