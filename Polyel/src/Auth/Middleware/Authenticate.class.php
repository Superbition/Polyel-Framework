<?php

namespace Polyel\Auth\Middleware;

use Polyel\Auth\AuthManager as Auth;
use Polyel\Auth\Middleware\Contracts\AuthenticationOutcomes;

abstract class Authenticate implements AuthenticationOutcomes
{
    public $middlewareType = "before";

    protected $auth;

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    public function process($request, $protector = null)
    {
        // Get the default protector is one is not provided...
        $protector = $protector ?: $this->getDefaultProtector();

        // Using the selected protector, set the source table to be used when authenticating users
        $this->auth->setSource($protector['source']);

        // Try and authenticate the user and get the outcome...
        $authenticated = $this->authenticate($request, $protector);

        // If false, it means the user is not authenticated...
        if($authenticated === false)
        {
            // Return an unauthenticated response
            return $this->unauthenticated();
        }

        // Return an authenticated response
        return $this->authenticated();
    }

    private function authenticate($request, $protector)
    {
        // Check the request to see if it is authenticated...
        return $this->auth->protector($protector['driver'])->check($request);
    }

    private function getDefaultProtector()
    {
        // Grabs the default protector that is set in the auth.php config file
        $defaultProtector = config('auth.defaults.protector');
        return config("auth.protectors.$defaultProtector");
    }
}