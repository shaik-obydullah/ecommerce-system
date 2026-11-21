<?php

declare(strict_types=1);

use App\Core\Cart\Exceptions\CartConflictException;
use App\Core\Cart\Services\CartManager;
use App\Http\Middleware\MergeGuestCart;
use App\Http\ProblemDetails;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The cart token is an opaque, httpOnly secret that must remain
        // readable by both the web and API middleware groups. It is not a
        // Laravel payload, so it bypasses cookie encryption.
        $middleware->encryptCookies(except: [
            CartManager::COOKIE,
        ]);

        $middleware->web(append: [
            MergeGuestCart::class,
        ]);

        $middleware->api(append: [
            MergeGuestCart::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return match (true) {
                $exception instanceof ValidationException => ProblemDetails::make(
                    $request,
                    422,
                    'Validation failed',
                    'The submitted data is invalid.',
                    $exception->errors(),
                ),
                $exception instanceof AuthenticationException => ProblemDetails::make(
                    $request,
                    401,
                    'Unauthenticated',
                    'Authentication is required to access this resource.',
                ),
                $exception instanceof AuthorizationException => ProblemDetails::make(
                    $request,
                    403,
                    'Forbidden',
                    'You are not allowed to perform this action.',
                ),
                $exception instanceof ModelNotFoundException,
                $exception instanceof NotFoundHttpException => ProblemDetails::make(
                    $request,
                    404,
                    'Not found',
                    'The requested resource was not found.',
                ),
                $exception instanceof CartConflictException => ProblemDetails::make(
                    $request,
                    409,
                    'Cart conflict',
                    $exception->getMessage(),
                    type: $exception->problemType(),
                ),
                $exception instanceof ThrottleRequestsException => ProblemDetails::make(
                    $request,
                    429,
                    'Too many requests',
                    'You have exceeded the request rate limit.',
                    headers: $exception->getHeaders(),
                ),
                $exception instanceof HttpExceptionInterface => ProblemDetails::make(
                    $request,
                    $exception->getStatusCode(),
                    Response::$statusTexts[$exception->getStatusCode()] ?? 'HTTP error',
                    $exception->getMessage() !== '' ? $exception->getMessage() : null,
                    headers: $exception->getHeaders(),
                ),
                default => ProblemDetails::make(
                    $request,
                    500,
                    'Server error',
                    config('app.debug') ? $exception->getMessage() : 'An unexpected error occurred.',
                ),
            };
        });
    })->create();
