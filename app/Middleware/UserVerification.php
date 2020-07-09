<?php

namespace App\Middleware;

use Polyel\Http\Request;
use Polyel\Auth\Middleware\MustVerifyEmail;

class UserVerification extends MustVerifyEmail
{
    public function additionalVerification(Request $request)
    {
        // ...
    }

    public function verificationFailed(Request $request)
    {
        // ...
    }
}