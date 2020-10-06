<?php

namespace App\Middleware;

use Polyel\Http\Request;
use Polyel\Auth\Middleware\MustVerifyEmail;

class UserVerification extends MustVerifyEmail
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