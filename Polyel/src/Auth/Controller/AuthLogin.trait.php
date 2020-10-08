<?php

namespace Polyel\Auth\Controller;

use Polyel\Http\Request;
use Polyel\Session\Session;

trait AuthLogin
{
    public function displayLoginView()
    {
        return response(view('auth.login:view'));
    }

    public function login(Request $request, Session $session)
    {
        // Pass over the request and attempt to login a user, using the data sent from the request
        if($this->attemptLogin($request))
        {
            // Because a login was successful, a user does not need to confirm their password again
            $session->store('lastPasswordConfirmation', time());

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
         * Get the username we will use to try and identify the user with.
         * We store the result here so we don't have to call this function more than once.
         */
        $username = $this->username($request);

        $data = $request->validate($this->validation($request));

        // See if there are any extra conditions to apply when search for a user in the database
        $additionalConditions = $this->additionalConditions($request);

        /*
         * Pull out the credentials from the POST request and use the
         * Session Protector from the Auth System to try and validate the given
         * credentials, returning true or false as the outcome.
         */
        $credentials[$username] = $data[$username];
        $credentials['password'] = $data['password'];
        return $this->auth->protector('session')->attemptLogin($credentials, $additionalConditions);
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
    private function loginNotSuccessful(Request $request)
    {
        // The developer failed method might want to return a response of its own...
        if($response = $this->failed($request))
        {
            return $response;
        }

        /*
         * No response from the dev, so we send back a basic 401 failed
         * authorization/unauthorized attempt or a normal web based
         * redirect with a added auth error message.
         */
        return $request->expectsJson()
            ? response('', 401)
            : redirect('/login')->withErrors([
                'auth' => 'Email or password is incorrect']);
    }

    public function logout(Request $request)
    {
        $this->auth->protector('session')->logout();

        if($response = $this->loggedOff($request))
        {
            return $response;
        }

        return response('', 204);
    }
}