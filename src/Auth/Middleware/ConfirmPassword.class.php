<?php

namespace Polyel\Auth\Middleware;

use Closure;
use Polyel\Http\Request;
use Polyel\Session\Session;
use Polyel\Auth\AuthManager as Auth;
use Polyel\Auth\Middleware\Contracts\PasswordConfirmationOutcomes;

abstract class ConfirmPassword implements PasswordConfirmationOutcomes
{
    protected $auth;

    private $session;

    public function __construct(Auth $auth, Session $session)
    {
        $this->auth = $auth;
        $this->session = $session;
    }

    public function process(Request $request, Closure $nextMiddleware)
    {
        if($this->requiresPasswordConfirmation())
        {
            // Store the intended location the user wanted to go
            $this->session->store('intendedConfirmURL', $request->path());

            // Give the developer a chance to perform actions when a password confirmation is triggered
            $this->passwordConfirmationRequired($request);

            // If no response is given we redirect to confirm the users password
            return redirect('/password/confirm');
        }

        return $nextMiddleware($request);
    }

    private function requiresPasswordConfirmation()
    {
        // Get the timestamp of when the password was last confirmed and take away from the current timestamp...
        $lastConfirmed = (time() - $this->session->get('lastPasswordConfirmation', 0));

        // Return the outcome if the last confirmed time breaks the limit set in the config
        return ($lastConfirmed > config('auth.password_confirmation_timeout'));
    }
}