<?php

return [

    /*
     * Used to shorten Middleware class names for use when
     * attaching a Middleware to a Route.
     */
    "keys" =>
    [
        "BeforeMiddlewareExample" => \App\Middleware\BeforeExampleMiddleware::class,

        "AfterMiddlewareExample" => \App\Middleware\AfterExampleMiddleware::class,
    ],

    /*
     * Middleware which runs globally either before or after a request.
     * The order of execution is respected from this configuration on both before
     * and after lists below.
     */
    "global" =>
    [
        "before" => [
            //"BeforeMiddlewareExample",
        ],

        "after" => [
            //"AfterMiddlewareExample",
        ],
    ],

];