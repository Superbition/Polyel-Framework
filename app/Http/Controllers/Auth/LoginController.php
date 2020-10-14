<?php

namespace App\Http\Controllers\Auth;

use Polyel\Http\Request;
use Polyel\Session\Session;
use Polyel\Auth\AuthManager;
use App\Http\Controllers\Controller;
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

    private $session;

    public function __construct(AuthManager $auth, Session $session)
    {
        $this->auth = $auth;
        $this->session = $session;
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
     * Setup the rules which validate the
     * data provided during registration.
     */
    private function validation(Request $request)
    {
        return [
            $this->username($request) => ['Break:rule', 'Required', 'Email'],
            'password' => ['Required', 'String'],
        ];
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
        //return ['banned' => [0, 'Your account has been banned and you cannot login']];
    }

    /*
     * A user has provided successful login credentials and have
     * been logged into your application, now we can decide what
     * happens next and where they will be directed with a valid
     * session.
     */
    private function success(Request $request, $user)
    {
        /*
         * Redirect the user to their intended destination if they
         * tried to access a certain page that requires authentication.
         */
        if($this->session->exists('intendedUrlAfterLogin'))
        {
            return redirect($this->session->pull('intendedUrlAfterLogin'));
        }

        return redirect('/');
    }

    /*
     * You may provide a custom response when the login details are incorrect.
     * By default the response from Polyel is used, a 401 JSON response is returned
     * if the request expects a JSON response or an error message using the name
     * 'auth' is displayed, saying the login details are incorrect.
     */
    private function failed(Request $request)
    {
        // ...
    }

    /*
     * Decide what happens next once a user
     * has been successfully logged out from their
     * current session.
     */
    public function loggedOff(Request $request)
    {
        return redirect('/');
    }
}