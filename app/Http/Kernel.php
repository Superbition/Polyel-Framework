<?php

namespace App\Http;

use Polyel\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected array $globalMiddlewareStack = [

        // ...

    ];

    protected array $middlewareGroups = [

        'web' => [

            \App\Http\Middleware\ValidateCsrfTokenMiddleware::class,

        ],

        'api' => [

            // ...

        ],

    ];

    protected array $routeMiddlewareAliases = [

        'RedirectIfAuthenticated' => \App\Http\Middleware\RedirectIfAuthenticatedMiddleware::class,
        'Auth' => \App\Http\Middleware\AuthenticateMiddleware::class,
        'IsVerified' => \App\Http\Middleware\UserVerificationMiddleware::class,
        'ConfirmPassword' => \App\Http\Middleware\ConfirmPasswordMiddleware::class,

    ];
}