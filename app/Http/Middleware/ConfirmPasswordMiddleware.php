<?php

namespace App\Http\Middleware;

use Polyel\Http\Request;
use Polyel\Auth\Middleware\ConfirmPassword as PolyelConfirmPasswordMiddleware;

class ConfirmPasswordMiddleware extends PolyelConfirmPasswordMiddleware
{
    /*
     * Perform any actions when a password confirmation has
     * been triggered and the user is required to confirm their
     * password again.
     */
    public function passwordConfirmationRequired(Request $request)
    {
        // ...
    }

    /*
     * Perform any actions for when the user does not need
     * to confirm their password because they have not yet
     * reached the timeout limit
     */
    public function passwordConfirmationNotRequired(Request $request)
    {
        // ...
    }
}