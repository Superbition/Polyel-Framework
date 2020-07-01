<?php

namespace App\Controllers\Auth;

use Polyel\Http\Request;
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

    /*
     * The username when to use when logging in a user from the request data
     * obtained from the POST request. By default this is set to be 'email'
     * but you may change this to something else, just make sure it links to your
     * form names and database field.
     */
    private function username(Request $request)
    {
        return 'email';
    }

    private function success(Request $request, $user)
    {
        return redirect('/');
    }

    private function failed(Request $request)
    {

    }

    public function loggedOff(Request $request)
    {
        return redirect('/');
    }
}