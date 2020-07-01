<?php

namespace App\Middleware;

use Polyel\Auth\Middleware\Authenticate as AuthenticationMiddleware;

class Authenticate extends AuthenticationMiddleware
{
    public function unauthenticated()
    {
        return redirect('/login');
    }

    public function authenticated()
    {
        // ...
    }
}