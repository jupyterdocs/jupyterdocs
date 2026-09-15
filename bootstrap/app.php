<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);

        $middleware->appendToGroup('web', [
            \App\Http\Middleware\EnsureUserIsActive::class,
            \App\Http\Middleware\TrackUserActivity::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (HttpException $e, $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->with('status', 'Your session timed out, so nothing was saved. Please try again.');
        });
    })->create();
