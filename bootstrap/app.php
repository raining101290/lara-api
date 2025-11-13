<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use App\Support\ApiResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // 1. VALIDATION ERRORS (422)
        $exceptions->render(function (ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error('Validation failed', $e->errors(), 422);
            }
        });

        // 2. AUTHENTICATION (401)
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error('Unauthorized access. Token required.', null, 401);
            }
        });

        // 3. JWT UNAUTHORIZED (Includes signature or malformed token)
        $exceptions->render(function (UnauthorizedHttpException $e, $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error('Unauthorized or invalid token', null, 401);
            }
        });

        // 4. JWT EXPIRED (TokenExp)
        $exceptions->render(function (TokenExpiredException $e, $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error('Token expired', null, 401);
            }
        });

        // 5. JWT INVALID (Signature mismatch, malformed)
        $exceptions->render(function (TokenInvalidException $e, $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error('Token invalid', null, 401);
            }
        });

        // 6. JWT MISSING
        $exceptions->render(function (JWTException $e, $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error('Token not provided', null, 401);
            }
        });

        // 7. MODEL NOT FOUND (404)
        $exceptions->render(function (ModelNotFoundException $e, $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error('Resource not found', null, 404);
            }
        });

        // 8. GENERIC SERVER ERROR (500)
        $exceptions->render(function (Throwable $e, $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error($e->getMessage(), null, 500);
            }
        });

    })
    ->create();
