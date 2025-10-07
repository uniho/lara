<?php

use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return \Application::configure(basePath: dirname(dirname(__DIR__)).'/laravel')
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ※
        \HQ::onMiddleware($middleware);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // ※
        \HQ::onExceptions($exceptions);
    })
    ->withProviders(\HQ::onProviders()) // ※
    ->create();
