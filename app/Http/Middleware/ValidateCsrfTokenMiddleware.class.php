<?php

namespace App\Http\Middleware;

use Polyel\Http\Middleware\CsrfTokenVerifier as PolyelCsrfTokenVerifierMiddleware;

class ValidateCsrfTokenMiddleware extends PolyelCsrfTokenVerifierMiddleware
{
    /*
     * URIs that shall be excluded from CSRF Token verification.
     */
    protected array $except = [

        '/api/*',

    ];
}