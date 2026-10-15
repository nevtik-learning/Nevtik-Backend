<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use App\Http\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->redirectGuestsTo(fn () => null);
        $middleware->redirectUsersTo(fn () => null);
        $middleware->alias([
        'role' => RoleMiddleware::class,
        ]);
        $middleware->throttleWithRedis();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
        if (! $request->is('api/*') && ! $request->wantsJson()) {
            return $e instanceof MethodNotAllowedHttpException ? redirect('/') : null;
        }

        $code = match (true) {
            $e instanceof AuthenticationException => 401,
            $e instanceof ValidationException => 422,
            $e instanceof HttpExceptionInterface => $e->getStatusCode(),
            default => 500,
        };

        $message = match (true) {
            $e instanceof ValidationException => 'data tidak valid',
            $code === 500 && ! config('app.debug') => 'terjadi kesalahan server',
            default => $e->getMessage(),
        };

        $body = ['status' => false, 'message' => $message, 'code' => $code];

        if ($e instanceof ValidationException) {
            $body['errors'] = $e->errors();
        }

        if (config('app.debug')) {
            $body['type'] = class_basename($e);
        }

        return response()->json(
            $body,
            $code,
            $e instanceof HttpExceptionInterface ? $e->getHeaders() : []
        );
      });
    })->create();
