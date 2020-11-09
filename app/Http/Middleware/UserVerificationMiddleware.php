<?php

namespace App\Http\Middleware;

use Polyel\Http\Request;
use Polyel\Auth\Middleware\MustVerifyEmail as PolyelMustVerifyEmailMiddleware;

class UserVerificationMiddleware extends PolyelMustVerifyEmailMiddleware
{
    /*
     * This method is called when email verification has failed.
     * You can use this method to return a custom response or perform an action.
     */
    public function verificationFailed(Request $request)
    {
        // ...
    }
}