<?php

namespace App\Middleware;

use Polyel\Http\Request;
use Polyel\Auth\Middleware\ConfirmPassword as ConfirmPasswordMiddleware;

class ConfirmPassword extends ConfirmPasswordMiddleware
{
    /*
     * What to di when the user has breached the password
     * confirmation timeout and needs to confirm their password again
     */
    public function passwordConfirmationRequired(Request $request)
    {
        return redirect('/password/confirm');
    }

    /*
     * Perform any actions for when the user does not need
     * to confirm their password because they have not yet
     * reached the timeout limit
     */
    public function passwordConfirmationNotRequired(Request $request)
    {

    }
}