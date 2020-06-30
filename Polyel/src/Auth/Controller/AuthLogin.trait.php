<?php

namespace Polyel\Auth\Controller;

use Polyel\Http\Request;

trait AuthLogin
{
    public function displayLoginView()
    {
        return response(view('auth.login:view'));
    }

    public function login(Request $request)
    {
        // Pass over the request and attempt to login a user, using the data sent from the request
        if($this->attemptLogin($request))
        {
            /*
             * If a request is successful, the user is loaded into the current session using
             * their ID, the session is also regenerated after login and then we call the
             * developers successful login method to send back a response if one is given...
             */
            return $this->loginSuccessful($request);
        }

        // The login attempt was not successful, invalid attempt
        return $this->loginNotSuccessful($request);
    }

    private function attemptLogin(Request $request)
    {
        /*
         * Pull out the credentials from the POST request and use the
         * Session Protector from the Auth System to try and validate the given
         * credentials, returning true or false as the outcome.
         */
        $credentials[$this->username] = $request->data($this->username);
        $credentials['password'] = $request->data('password');
        return $this->auth->protector('session')->attemptLogin($credentials);
    }

    /*
     * The method called when a login is successful.
     * Handles how to return a successful response to the client.
     */
    private function loginSuccessful($request)
    {
        // The developer success method might want to return a response of its own...
        if($response = $this->success($request, $this->auth->protector('session')->user()))
        {
            return $response;
        }

        // No response from the dev, so we send back a basic 204 no-content/success response
        return response('', 204);
    }

    /*
     * The method called when the login was invalid and not successful.
     * Handles how to return a failed login attempt response to the client.
     */
    private function loginNotSuccessful($request)
    {
        // The developer failed method might want to return a response of its own...
        if($response = $this->failed($request))
        {
            return $response;
        }

        // No response from the dev, so we send back a basic 401 failed authorization/unauthorized attempt
        return response('', 401);
    }

    private function logout(Request $request)
    {
        $this->auth->protector('session')->logout();

        if($response = $this->loggedOff($request))
        {
            return $response;
        }

        return response('', 204);
    }
}