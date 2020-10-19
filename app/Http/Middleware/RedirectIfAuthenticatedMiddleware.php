<?php

namespace App\Http\Middleware;

use Closure;
use Polyel\Http\Request;
use Polyel\Auth\AuthManager;

class RedirectIfAuthenticatedMiddleware
{
    // Where to redirect the user if they are already authenticated
    private string $home = '/';

    private $auth;

    public function __construct(AuthManager $auth)
    {
        $this->auth = $auth;
    }

    public function process(Request $request, Closure $nextMiddleware)
    {
        if($this->auth->protector('session')->check())
        {
            return redirect($this->home);
        }

        return $nextMiddleware($request);
    }
}