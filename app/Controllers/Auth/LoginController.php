<?php

namespace App\Controllers\Auth;

use Polyel\Http\Request;
use Polyel\Auth\AuthManager;
use App\Controllers\Controller;
use Polyel\Auth\Controller\AuthLogin;

class LoginController extends Controller
{
    /*
    │------------------------------------------------------------------------------
    │ Login Controller
    │------------------------------------------------------------------------------
    │ This controller handles user authentication and will process a user’s login
    | request using the Polyel authentication system. Most of the functionality is
    | already provided for you by the login trait which includes all of the login
    | functionality out of the box for you. You may use the success, failed and
    | loggedOff methods to alter what happens on those events before the default
    | Polyel outcome is used if you don't return a response.
    │
    */

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

    /*
     * Use this method to return any additional conditions for when
     * a user is attempting to login to your application. For example you
     * could add an extra condition to check if the user is not banned etc.
     * This method is called just before attempting to find the user in the
     * database.
     */
    public function additionalConditions(Request $request)
    {
        //return ['banned' => 0];
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