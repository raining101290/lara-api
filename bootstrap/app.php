<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use PHPOpenSourceSaver\JWTAuth\Http\Middleware\Authenticate as JWTAuthenticate;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Exception $e, $request) {
            if ($request->is('api/*')) {

                // If authentication fails (no token, bad token, guard blocks it)
                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized access. Token required.'
                    ], 401);
                }

                // JWT specific errors
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized or invalid token'
                    ], 401);
                }

                if ($e instanceof \PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException) {
                    return response()->json(['success' => false, 'message' => 'Token expired'], 401);
                }

                if ($e instanceof \PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException) {
                    return response()->json(['success' => false, 'message' => 'Token invalid'], 401);
                }

                if ($e instanceof \PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException) {
                    return response()->json(['success' => false, 'message' => 'Token not provided'], 401);
                }
            }
        });
    })->create();
