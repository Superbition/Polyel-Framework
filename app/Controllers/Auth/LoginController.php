<?php

namespace App\Controllers\Auth;

use Polyel\Auth\AuthManager;
use App\Controllers\Controller;
use Polyel\Auth\Controller\AuthLogin;

class LoginController extends Controller
{
    use AuthLogin;

    /*
     * The field to be used as the main username when trying to
     * login a user from the request data obtained from the login
     * POST request. By default this is set to be 'email' but you may
     * change this to something else, just make sure it links to your
     * form names.
     */
    private string $username = 'email';

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