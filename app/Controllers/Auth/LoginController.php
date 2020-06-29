<?php

namespace App\Controllers\Auth;

use Polyel\Auth\AuthManager;
use App\Controllers\Controller;
use Polyel\Auth\Controller\AuthLogin;

class LoginController extends Controller
{
    use AuthLogin;

    private $auth;

    public function __construct(AuthManager $auth)
    {
        $this->auth = $auth;
    }

    private function success()
    {

    }

    private function failed()
    {

    }

    public function loggedOff()
    {

    }
}