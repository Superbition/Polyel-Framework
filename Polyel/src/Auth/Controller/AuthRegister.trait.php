<?php

namespace Polyel\Auth\Controller;

use Polyel\Http\Request;
use Polyel\Auth\SendsVerificationEmail;

trait AuthRegister
{
    use SendsVerificationEmail;

    public function displayRegistrationView()
    {
        return response(view('auth.register:view'));
    }

    public function register(Request $request)
    {
        // Validate incoming user registration data
        $data = $request->validate($this->validation());

        // Let the main Register method actually create the user, just pass the request data over
        $id = $this->create($data);

        // Login the newly created user by their ID
        $this->auth->protector('session')->loginById($id);

        /*
         * Once the user is created and logged in, run the completed registration
         * function and get the response is one is returned, if one is we use the devs
         * provided response.
         */
        if($response = $this->registered($request, $id))
        {
            return $response;
        }

        /*
         * If no response is provided, we send back a normal 201 response to indicate a user was created
         * or redirect the user to the index route.
         */
        return $request->expectsJson()
            ? response('', 201)
            : redirect('/');
    }
}