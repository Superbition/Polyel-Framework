<?php

namespace App\Middleware;

use Polyel\Auth\Middleware\Authenticate as AuthenticationMiddleware;

class Authenticate extends AuthenticationMiddleware
{
    /*
     * Called when a web request is not authenticated, the user is not logged in
     */
    public function unauthenticated()
    {
        return redirect('/login');
    }

    /*
     * Called when a user is authenticated and is valid during a web request
     */
    public function authenticated()
    {
        // ...
    }
}