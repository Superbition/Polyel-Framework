<?php

namespace App\Middleware;

use Polyel\Middleware\CsrfTokenVerifier as CsrfTokenVerifierMiddleware;

class ValidateCsrfToken extends CsrfTokenVerifierMiddleware
{
    /*
     * URIs that shall be excluded from CSRF Token verification.
     */
    protected array $except = [

        '/api/*',

    ];
}