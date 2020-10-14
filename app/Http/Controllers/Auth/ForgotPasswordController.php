<?php

namespace App\Http\Controllers\Auth;

use Polyel\Auth\AuthManager;
use App\Http\Controllers\Controller;
use Polyel\Auth\Controller\AuthForgotPassword;

class ForgotPasswordController extends Controller
{
    /*
    │------------------------------------------------------------------------------
    │ Forgot Password Controller
    │------------------------------------------------------------------------------
    | This controller is responsible for handling requests when a user forgets
    | their password. All of the main functionality is provided by the included
    | trait called AuthForgotPassword. An email is required by default in order to
    | initiate a password reset link to be sent out.
    │
    */

    use AuthForgotPassword;

    private $auth;

    // Used when redirecting after a reset email has been sent
    private $redirectTo = '/';

    public function __construct(AuthManager $auth)
    {
        $this->auth = $auth;
    }

    public function validation()
    {
        return [
            'email' => ['Required', 'Email:dns,spoof'],
        ];
    }
}