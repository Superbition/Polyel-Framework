<?php

namespace App\Http\Controllers\Auth;

use Polyel\Auth\AuthManager;
use App\Http\Controllers\Controller;
use Polyel\Auth\Controller\AuthConfirmPassword;

class ConfirmPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Controller
    |--------------------------------------------------------------------------
    | This controller will handle password confirmations when the timeout
    | (set in the auth config) is reached and the user needs to confirm their
    | password again in order to access the intended page. This controller will
    | also redirect the user to their intended URL if the password confirmation
    | is successful.
    |
    */

    use AuthConfirmPassword;

    private $auth;

    public function __construct(AuthManager $auth)
    {
        $this->auth = $auth;
    }

    public function validation()
    {
        return [
            'password' => ['Required', 'String', 'Min:6'],
        ];
    }
}