<?php

namespace Polyel\Auth\Middleware;

use Polyel\Session\Session;
use Polyel\Auth\AuthManager as Auth;
use Polyel\Auth\Middleware\Contracts\AuthenticationOutcomes;

abstract class Authenticate implements AuthenticationOutcomes
{
    public $middlewareType = "before";

    protected $auth;

    protected $session;

    public function __construct(Auth $auth, Session $session)
    {
        $this->auth = $auth;
        $this->session = $session;
    }

    public function process($request, $protector = null)
    {
        // Get the default protector is one is not provided...
        $protector = $protector ?: $this->getDefaultProtector();

        // Get the protector config: source & driver
        $protector = $this->getProtectorConfig($protector);

        // Using the selected protector, set the source table to be used when authenticating users
        $this->auth->setSource($protector['source']);

        // Try and authenticate the user and get the outcome...
        $authenticated = $this->authenticate($request, $protector);

        // If false, it means the user is not authenticated...
        if($authenticated === false)
        {
            if($request->type === 'web')
            {
                // Can be used to redirect the user after logon...
                $this->session->store('intendedUrlAfterLogin', $request->url());
            }

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

    private function getProtectorConfig($protector)
    {
        // Return the selected protector config
        return config("auth.protectors.$protector");
    }

    private function getDefaultProtector()
    {
        // Gets the default protector that is set in the auth.php config file
        return config('auth.defaults.protector');
    }
}