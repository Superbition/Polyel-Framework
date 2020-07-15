<?php

namespace App\Middleware;

use Polyel\Http\Request;
use Polyel\Auth\Middleware\MustVerifyEmail;

class UserVerification extends MustVerifyEmail
{
    /*
     * Here you may perform additional verification once the users email address has been
     * confirmed and validated.
     */
    public function additionalVerification(Request $request)
    {
        // ...
    }

    /*
     * This method is called when email verification has failed.
     * You can use this method to return a custom response or perform an action.
     */
    public function verificationFailed(Request $request)
    {
        // ...
    }
}