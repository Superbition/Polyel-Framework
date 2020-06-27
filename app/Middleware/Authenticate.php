<?php

namespace App\Middleware;

use Polyel\Auth\Middleware\Authenticate as AuthenticationMiddleware;

class Authenticate extends AuthenticationMiddleware
{
    public function unauthenticated()
    {

    }

    public function authenticated()
    {
        return redirect('/', 301);
    }
}