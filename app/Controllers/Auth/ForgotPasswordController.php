<?php

namespace App\Controllers\Auth;

use Polyel\Auth\AuthManager;
use App\Controllers\Controller;
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

    public function __construct(AuthManager $auth)
    {
        $this->auth = $auth;
    }
}