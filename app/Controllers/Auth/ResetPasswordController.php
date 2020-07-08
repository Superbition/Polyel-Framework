<?php

namespace App\Controllers\Auth;

use Polyel\Auth\AuthManager;
use App\Controllers\Controller;
use Polyel\Auth\Controller\AuthResetPassword;

class ResetPasswordController extends Controller
{
    /*
    │------------------------------------------------------------------------------
    │ Password Reset Controller
    │------------------------------------------------------------------------------
    │ This controller handles password resets for a user, it provides you will
    | all the required functionality to quickly get setup to allow a user to
    | reset their password if they forget it. This controller uses a trait called
    | AuthResetPassword which provides the main functionality.
    │
    */

    use AuthResetPassword;

    private $auth;

    public function __construct(AuthManager $auth)
    {
        $this->auth = $auth;
    }
}