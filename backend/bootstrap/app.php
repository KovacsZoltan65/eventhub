<?php

// +++ HIBAKEZELÉSHEZ SZÜKSÉGES USE +++

//use Throwable;

// +++ SPATIE MIDDLEWAREK +++


// +++ SANCTUM STATEFUL +++


use App\Http\Middleware\EnsureUserIsNotBlocked;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\ThrottleRequestsException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
        
    ->withMiddleware(function (Middleware $middleware): void {
        
        //\App\Http\Middleware\EnsureUserIsNotBlocked::class;
        
        // Saját middleware alias (ha szeretnél rövid nevet használni):
        $middleware->alias([
            'blocked' => EnsureUserIsNotBlocked::class,
            // Spatie route middleware aliasok
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
        
        /*
        // Spatie route middleware aliasok
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
        */
        
        // Sanctum SPA stateful az API csoporton
        $middleware->appendToGroup('api', EnsureFrontendRequestsAreStateful::class);
        
    })
    
    ->withExceptions(function (Exceptions $exceptions): void {
        $isApi = fn ($request) => $request->expectsJson() || $request->is('api/*');
        
        $exceptions->renderable(function (ValidationException $e, $request) use ($isApi) {
            if ($isApi($request)) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });
        
        $exceptions->renderable(function (AuthenticationException $e, $request) use ($isApi) {
            if ($isApi($request)) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
        });
        
        $exceptions->renderable(function (AuthorizationException $e, $request) use ($isApi) {
            if ($isApi($request)) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        });
        
        $exceptions->renderable(function (ModelNotFoundException $e, $request) use ($isApi) {
            if ($isApi($request)) {
                return response()->json(['message' => 'Not found'], 404);
            }
        });
        
        $exceptions->renderable(function (ThrottleRequestsException $e, $request) use ($isApi) {
            if ($isApi($request)) {
                return response()->json(['message' => 'Too many requests'], 429);
            }
        });
        
        // Általános fallback API kérésekre – ne szivárogjon stack trace
        $exceptions->render(function (Throwable $e, $request) use ($isApi) {
            if ($isApi($request)) {
                $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
                if ($status === 500) {
                    return response()->json(['message' => 'Server error'], 500);
                }
                // Más HTTP hibákra hagyjuk a Laravel defaultot, vagy alakítsd igény szerint
            }
        });
        
    })->create();
