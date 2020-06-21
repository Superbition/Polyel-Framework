<?php

namespace App\Middleware;

use Polyel\Middleware\CsrfTokenVerifier;

class ValidateCsrfToken extends CsrfTokenVerifier
{
    /*
     * URIs that shall be excluded from CSRF Token verification.
     */
    protected array $except = [

        //...

    ];
}