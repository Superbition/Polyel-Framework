<?php

return [

    /*
     * Used to shorten Middleware class names for use when
     * attaching a Middleware to a Route.
     */
    "keys" =>
    [
        "ValidateCsrfToken" => \App\Http\Middleware\ValidateCsrfTokenMiddleware::class,

        "RedirectIfAuthenticated" => \App\Http\Middleware\RedirectIfAuthenticatedMiddleware::class,

        "Auth" => \App\Http\Middleware\AuthenticateMiddleware::class,

        "IsVerified" => \App\Http\Middleware\UserVerificationMiddleware::class,

        "ConfirmPassword" => \App\Http\Middleware\ConfirmPasswordMiddleware::class,
    ],

    /*
     * Middleware which runs globally either before or after a request.
     * The order of execution is respected from this configuration on both before
     * and after lists below.
     */
    "global" =>
    [
        "before" => [
            "ValidateCsrfToken",
        ],

        "after" => [
            // ...
        ],
    ],

];