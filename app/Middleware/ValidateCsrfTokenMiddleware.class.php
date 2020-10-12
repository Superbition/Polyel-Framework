<?php

namespace App\Middleware;

use Polyel\Middleware\CsrfTokenVerifier as PolyelCsrfTokenVerifierMiddleware;

class ValidateCsrfTokenMiddleware extends PolyelCsrfTokenVerifierMiddleware
{
    /*
     * URIs that shall be excluded from CSRF Token verification.
     */
    protected array $except = [

        '/api/*',

    ];
}