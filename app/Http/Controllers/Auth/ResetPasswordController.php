<?php

namespace App\Http\Controllers\Auth;

use Polyel\Auth\AuthManager;
use App\Http\Controllers\Controller;
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

    public function validation()
    {
        return [
            'Email' => ['Required', 'Email:dns,spoof'],
            'Password' => ['Required', 'String', 'Min:6', 'Confirmed'],
            'token' => ['Required'],
        ];
    }
}