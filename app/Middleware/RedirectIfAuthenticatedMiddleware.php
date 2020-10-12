<?php

namespace App\Middleware;

use Polyel\Auth\AuthManager;

class RedirectIfAuthenticatedMiddleware
{
    public $middlewareType = "before";

    // Where to redirect the user if they are already authenticated
    private string $home = '/';

    private $auth;

    public function __construct(AuthManager $auth)
    {
        $this->auth = $auth;
    }

    public function process($request)
    {
        if($this->auth->protector('session')->check())
        {
            return redirect($this->home);
        }
    }
}