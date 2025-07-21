<?php

use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
        $exceptions->render(function (Throwable $e, $request) {
            // isHttpException() in framework/src/Illuminate/Foundation/Exceptions/Handler.php
            if (\HQ::getDebugShowSource() || $e instanceof HttpExceptionInterface) {
                return false;
            }

            return new SymfonyResponse(
                'PHP ERROR 500', 500, []
            );
        });
    })->create();
